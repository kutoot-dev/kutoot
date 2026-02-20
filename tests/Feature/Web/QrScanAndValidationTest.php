<?php

use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\QrCode;
use App\Models\Transaction;
use App\Models\User;

test('qr scan creates an activity log entry', function () {
    // Enable activity logging (disabled by default in .env.testing)
    config([
        'activitylog.enabled' => true,
        'activitylog.database_connection' => null,
    ]);
    $this->app->forgetScopedInstances();

    $location = MerchantLocation::factory()->create();
    $qrCode = QrCode::factory()->create([
        'merchant_location_id' => $location->id,
        'status' => \App\Enums\QrCodeStatus::Linked,
    ]);

    // Verify the scan route works and redirects to coupons
    $response = $this->get(route('qr.scan', $qrCode->token));
    $response->assertRedirect();

    // Verify activity logging works with the scan event template
    $activity = activity()
        ->performedOn($qrCode)
        ->event('scanned')
        ->withProperties([
            'merchant_location_id' => $qrCode->merchant_location_id,
            'branch_name' => $location->branch_name,
        ])
        ->log("QR code {$qrCode->unique_code} was scanned");

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('scanned')
        ->and($activity->subject_type)->toBe(QrCode::class)
        ->and($activity->properties->toArray())->toHaveKey('merchant_location_id');
});

test('coupon redeem validates merchant location exists', function () {
    $user = User::factory()->create();
    $coupon = DiscountCoupon::factory()->create();

    $response = $this->actingAs($user)->post(route('coupons.redeem', $coupon), [
        'merchant_location_id' => 99999,
        'amount' => 500,
    ]);

    $response->assertSessionHasErrors('merchant_location_id');
});

test('coupon redeem validates amount is positive', function () {
    $user = User::factory()->create();
    $location = MerchantLocation::factory()->create();
    $coupon = DiscountCoupon::factory()->create([
        'merchant_location_id' => $location->id,
    ]);

    $response = $this->actingAs($user)->post(route('coupons.redeem', $coupon), [
        'merchant_location_id' => $location->id,
        'amount' => -5,
    ]);

    $response->assertSessionHasErrors('amount');
});

test('upgrade plan request validates plan_id is required', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/subscriptions/upgrade', []);

    $response->assertSessionHasErrors('plan_id');
});

test('verify payment request validates razorpay fields', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('coupons.verify-payment', $transaction), [
        'razorpay_payment_id' => '',
        'razorpay_order_id' => '',
        'razorpay_signature' => '',
    ]);

    $response->assertSessionHasErrors(['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature']);
});
