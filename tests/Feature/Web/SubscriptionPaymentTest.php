<?php

use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Payments\RazorpayGateway;

beforeEach(function () {
    config([
        'app.plan_tax_type' => 'exclusive',
        'app.gst_rate' => 18,
    ]);
    // Tests run in 'testing' environment where app()->isProduction() is false,
    // so non-production (debug) behavior is active by default.
});

test('free plan is activated directly without payment', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 0,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    $response = $this->actingAs($user)->post('/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ]);

    $response->assertRedirect();

    $subscription = UserSubscription::where('user_id', $user->id)
        ->where('plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status->value)->toBe('active');
});

test('paid plan creates transaction in debug mode and activates', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    $response = $this->actingAs($user)->post('/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ]);

    $response->assertRedirect();

    // Transaction should be created with plan_purchase type
    $transaction = Transaction::where('user_id', $user->id)
        ->where('type', TransactionType::PlanPurchase)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->payment_status)->toBe(PaymentStatus::Completed)
        ->and((float) $transaction->total_amount)->toBeGreaterThan(0);

    // Subscription should be active
    $subscription = UserSubscription::where('user_id', $user->id)
        ->where('plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status->value)->toBe('active');
});

test('paid plan calculates exclusive GST correctly in debug mode', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 1000,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    $this->actingAs($user)->post('/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ]);

    $transaction = Transaction::where('user_id', $user->id)
        ->where('type', TransactionType::PlanPurchase)
        ->latest()
        ->first();

    // Exclusive: base = 1000, GST = 180, total = 1180
    expect((float) $transaction->original_bill_amount)->toBe(1000.00)
        ->and((float) $transaction->gst_amount)->toBe(180.00)
        ->and((float) $transaction->total_amount)->toBe(1180.00);
});

test('default plan cannot be manually upgraded to', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'is_default' => true,
    ]);

    $response = $this->actingAs($user)->post('/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('paid plan in production mode returns razorpay order data', function () {
    // Simulate production environment where isProduction() returns true
    app()->detectEnvironment(fn () => 'production');

    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    // Mock the Razorpay Gateway
    $mockGateway = Mockery::mock(RazorpayGateway::class);
    $mockGateway->shouldReceive('createPlanOrder')
        ->once()
        ->andReturn([
            'id' => 'order_test123',
            'amount' => 58882,
            'currency' => 'INR',
            'key' => 'rzp_test_key',
            'merchant_name' => 'Kutoot',
        ]);

    // Bind mock into the PaymentManager
    $this->app->bind(RazorpayGateway::class, fn () => $mockGateway);

    // We also need to mock the PaymentManager to return our mocked gateway
    $mockManager = Mockery::mock(\App\Services\Payments\PaymentManager::class);
    $mockManager->shouldReceive('getDefaultDriver')->andReturn('razorpay');
    $mockManager->shouldReceive('driver')->andReturn($mockGateway);
    $this->app->instance(\App\Services\Payments\PaymentManager::class, $mockManager);

    // In production mode the normal testing CSRF bypass is disabled, so we
    // need to send a valid token just like the front-end fetch handler would.
    $token = csrf_token();

    $response = $this->actingAs($user)
        ->withSession(['_token' => $token])
        ->postJson('/subscriptions/upgrade', [
            'plan_id' => $plan->id,
        ], [
            'X-CSRF-TOKEN' => $token,
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['order', 'transaction_id', 'plan_id']);

    // Transaction should be pending
    $transaction = Transaction::where('user_id', $user->id)->latest()->first();
    expect($transaction->payment_status)->toBe(PaymentStatus::Pending)
        ->and($transaction->razorpay_order_id)->toBe('order_test123');
});


// If the client fails to send the CSRF token cookie/header we expect a 419
// response. This replicates the error seen when the fetch call omitted
// credentials. The hook changes above will prevent that from happening.
test('upgrade route returns 419 when no csrf token is provided in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/subscriptions/upgrade', [
            'plan_id' => $plan->id,
        ]);

    $response->assertStatus(419);
});

test('plan payment verification activates subscription', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    $transaction = Transaction::factory()->planPurchase()->create([
        'user_id' => $user->id,
        'total_amount' => 588.82,
        'original_bill_amount' => 499,
        'amount' => 499,
        'gst_amount' => 89.82,
        'razorpay_order_id' => 'order_test456',
        'payment_status' => PaymentStatus::Pending,
    ]);

    // Mock gateway for verification
    $mockGateway = Mockery::mock(RazorpayGateway::class);
    $mockGateway->shouldReceive('verifyPayment')->once()->andReturn(true);

    $mockManager = Mockery::mock(\App\Services\Payments\PaymentManager::class);
    $mockManager->shouldReceive('driver')->andReturn($mockGateway);
    $this->app->instance(\App\Services\Payments\PaymentManager::class, $mockManager);

    $response = $this->actingAs($user)->post("/subscriptions/verify-payment/{$transaction->id}", [
        'razorpay_order_id' => 'order_test456',
        'razorpay_payment_id' => 'pay_test789',
        'razorpay_signature' => 'sig_test',
        'plan_id' => $plan->id,
    ]);

    $response->assertRedirect(route('subscriptions.index'));

    $transaction->refresh();
    expect($transaction->payment_status)->toBe(PaymentStatus::Completed)
        ->and($transaction->payment_id)->toBe('pay_test789');

    $subscription = UserSubscription::where('user_id', $user->id)
        ->where('plan_id', $plan->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status->value)->toBe('active');
});
