<?php

use App\Enums\DiscountType;
use App\Events\CouponRedeemed;
use App\Models\CouponCategory;
use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CouponRedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CouponRedemptionService::class);
    Event::fake([CouponRedeemed::class]);
});

test('it redeems a valid coupon and fires event', function () {
    $category = CouponCategory::factory()->create();
    $location = MerchantLocation::factory()->create();
    $user = User::factory()->create();

    $coupon = DiscountCoupon::factory()->create([
        'coupon_category_id' => $category->id,
        'is_active' => true,
        'discount_type' => DiscountType::Fixed,
        'discount_value' => 10.00,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'merchant_location_id' => $location->id,
        'amount' => 100.00,
    ]);

    $redemption = $this->service->redeemCoupon($user, $coupon, $transaction);

    expect($redemption)->not->toBeNull();
    expect($redemption->user_id)->toBe($user->id);
    expect($redemption->coupon_id)->toBe($coupon->id);
    expect($redemption->discount_applied)->toEqual(10.00);

    Event::assertDispatched(CouponRedeemed::class);
});

test('it throws validation exception for inactive coupon', function () {
    $coupon = DiscountCoupon::factory()->create(['is_active' => false]);
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create();

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('This coupon is no longer active.');

    $this->service->redeemCoupon($user, $coupon, $transaction);
});

test('it calculates percentage discount correctly', function () {
    $coupon = DiscountCoupon::factory()->create([
        'is_active' => true,
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 20, // 20%
    ]);
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['amount' => 100.00]);

    $redemption = $this->service->redeemCoupon($user, $coupon, $transaction);

    expect($redemption->discount_applied)->toEqual(20.00);
});
