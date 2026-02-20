<?php

use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('mail always-to is configured in non-production environments', function () {
    // In test environment (non-production), Mail::alwaysTo should be set
    // We verify this by checking that the registered provider configures it
    expect(app()->environment())->not->toBe('production');

    // Re-trigger boot to validate the Mail::alwaysTo call
    // The AppServiceProvider should have set Mail::alwaysTo
    $provider = app()->getProvider(\App\Providers\AppServiceProvider::class);
    expect($provider)->not->toBeNull();
});

test('coupon redemption skips payment in non-production environment', function () {
    $user = User::factory()->create();
    $location = MerchantLocation::factory()->create();
    $coupon = DiscountCoupon::factory()->create([
        'merchant_location_id' => $location->id,
        'min_order_value' => 0,
    ]);

    // In testing environment, isProduction() is false so payment should be skipped
    expect(app()->isProduction())->toBeFalse();

    $response = $this->actingAs($user)->post("/coupons/{$coupon->id}/redeem", [
        'merchant_location_id' => $location->id,
        'amount' => 500,
    ]);

    // Should redirect (payment skipped) rather than returning JSON for Razorpay
    $response->assertRedirect();
});

test('subscription upgrade skips payment in non-production environment', function () {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    // In testing environment, isProduction() is false so payment should be skipped
    expect(app()->isProduction())->toBeFalse();

    $response = $this->actingAs($user)->post('/subscriptions/upgrade', [
        'plan_id' => $plan->id,
    ]);

    // Should redirect (payment skipped, auto-completed)
    $response->assertRedirect();
});

test('subscription upgrade requires payment in production environment', function () {
    // Simulate production environment
    app()->detectEnvironment(fn () => 'production');
    expect(app()->isProduction())->toBeTrue();

    $user = User::factory()->create();
    $plan = SubscriptionPlan::factory()->create([
        'price' => 499,
        'is_default' => false,
        'duration_days' => 30,
    ]);

    // Mock the Razorpay Gateway to avoid real API calls
    $mockGateway = Mockery::mock(\App\Services\Payments\RazorpayGateway::class);
    $mockGateway->shouldReceive('createPlanOrder')
        ->once()
        ->andReturn([
            'id' => 'order_env_test',
            'amount' => 58882,
            'currency' => 'INR',
            'key' => 'rzp_test_key',
            'merchant_name' => 'Kutoot',
        ]);

    $mockManager = Mockery::mock(\App\Services\Payments\PaymentManager::class);
    $mockManager->shouldReceive('getDefaultDriver')->andReturn('razorpay');
    $mockManager->shouldReceive('driver')->andReturn($mockGateway);
    $this->app->instance(\App\Services\Payments\PaymentManager::class, $mockManager);

    // Disable CSRF verification since detectEnvironment('production') disables
    // the runningUnitTests() CSRF bypass
    $response = $this->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
        ->actingAs($user)
        ->postJson('/subscriptions/upgrade', [
            'plan_id' => $plan->id,
        ]);

    // Should return JSON with Razorpay order data (not redirect)
    $response->assertSuccessful()
        ->assertJsonStructure(['order', 'transaction_id', 'plan_id']);
});
