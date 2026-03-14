<?php

use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── Coupon Listing ───────────────────────────────────────────────────────

it('lists available coupons for authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    DiscountCoupon::factory()->count(3)->create();

    $this->getJson('/api/v1/coupons')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                // structure with wildcard so empty arrays are still accepted
                'plan_coupons' => ['*' => ['status']],
                'store_coupons' => ['*' => ['status']],
                'other_coupons' => ['*' => ['status']],
            ],
        ]);

});

it('requires auth to list coupons', function () {
    $this->getJson('/api/v1/coupons')
        ->assertUnauthorized();
});

it('shows a single coupon', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $coupon = DiscountCoupon::factory()->create();

    $this->getJson('/api/v1/coupons/'.$coupon->id)
        ->assertSuccessful()
        ->assertJsonPath('data.id', $coupon->id);
});

// ── Coupon Redemption ────────────────────────────────────────────────────

it('validates required fields for coupon redemption', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $coupon = DiscountCoupon::factory()->create();

    $this->postJson('/api/v1/coupons/'.$coupon->id.'/redeem', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['merchant_location_id', 'amount']);
});

it('validates amount minimum for redemption', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $coupon = DiscountCoupon::factory()->create();
    $location = MerchantLocation::factory()->create();

    $this->postJson('/api/v1/coupons/'.$coupon->id.'/redeem', [
        'merchant_location_id' => $location->id,
        'amount' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

// ── Verify Payment ───────────────────────────────────────────────────────

it('validates required fields for payment verification', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/coupons/verify-payment', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['razorpay_payment_id', 'razorpay_order_id', 'razorpay_signature']);
});
