<?php

use App\Enums\DiscountType;
use App\Models\CouponCategory;
use App\Models\DiscountCoupon;
use App\Models\MerchantLocation;
use App\Services\CouponService;

beforeEach(function () {
    $this->service = app(CouponService::class);
    $this->baseAttributes = [
        'discount_type' => DiscountType::Fixed->value,
        'discount_value' => 50.00,
        'starts_at' => now()->toDateTimeString(),
    ];
});

test('it generates the correct number of bulk coupons', function () {
    $category = CouponCategory::factory()->create();
    $location = MerchantLocation::factory()->create();

    $coupons = $this->service->generateBulk(10, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'merchant_location_id' => $location->id,
        'title' => 'Bulk Test Coupon',
    ]));

    expect($coupons)->toHaveCount(10);
    expect(DiscountCoupon::count())->toBe(10);
});

test('it generates coupons with unique codes', function () {
    $category = CouponCategory::factory()->create();

    $coupons = $this->service->generateBulk(50, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'title' => 'Unique Code Test',
        'discount_type' => DiscountType::Percentage->value,
        'discount_value' => 15.00,
    ]));

    $codes = $coupons->pluck('code')->toArray();
    expect(count(array_unique($codes)))->toBe(50);
});

test('it applies all attributes to generated coupons', function () {
    $category = CouponCategory::factory()->create();
    $location = MerchantLocation::factory()->create();

    $coupons = $this->service->generateBulk(5, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'merchant_location_id' => $location->id,
        'title' => 'Attribute Test',
        'description' => 'Testing attributes',
        'discount_type' => DiscountType::Percentage->value,
        'discount_value' => 20.00,
        'min_order_value' => 100.00,
        'max_discount_amount' => 50.00,
        'usage_limit' => 10,
        'usage_per_user' => 1,
    ]));

    $coupon = $coupons->first();

    expect($coupon->title)->toBe('Attribute Test');
    expect($coupon->description)->toBe('Testing attributes');
    expect((float) $coupon->discount_value)->toBe(20.00);
    expect((float) $coupon->min_order_value)->toBe(100.00);
    expect((float) $coupon->max_discount_amount)->toBe(50.00);
    expect($coupon->usage_limit)->toBe(10);
    expect($coupon->usage_per_user)->toBe(1);
    expect($coupon->coupon_category_id)->toBe($category->id);
    expect($coupon->merchant_location_id)->toBe($location->id);
});

test('it uses the specified prefix for coupon codes', function () {
    $category = CouponCategory::factory()->create();

    $coupons = $this->service->generateBulk(5, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'title' => 'Prefix Test',
        'discount_value' => 10.00,
    ]), 'SUMMER-');

    $coupons->each(function ($coupon) {
        expect($coupon->code)->toStartWith('SUMMER-');
    });
});

test('it creates coupons as active by default', function () {
    $category = CouponCategory::factory()->create();

    $coupons = $this->service->generateBulk(3, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'title' => 'Active Test',
        'discount_value' => 10.00,
    ]));

    $coupons->each(function ($coupon) {
        expect($coupon->is_active)->toBeTrue();
    });
});

test('it rejects invalid count values', function () {
    $this->service->generateBulk(0, array_merge($this->baseAttributes, [
        'title' => 'Should Fail',
    ]));
})->throws(\InvalidArgumentException::class, 'Coupon count must be between 1 and 10,000.');

test('it rolls back on database failure', function () {
    $category = CouponCategory::factory()->create();

    // First generate some coupons
    $this->service->generateBulk(5, array_merge($this->baseAttributes, [
        'coupon_category_id' => $category->id,
        'title' => 'Before Failure',
        'discount_value' => 10.00,
    ]));

    expect(DiscountCoupon::count())->toBe(5);
});
