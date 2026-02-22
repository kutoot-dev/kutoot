<?php

use App\Enums\SubscriptionStatus;
use App\Models\CouponCategory;
use App\Models\DiscountCoupon;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

beforeEach(function () {
    $this->basePlan = SubscriptionPlan::factory()->create([
        'name' => 'Base Plan',
        'is_default' => true,
        'price' => 0,
        'duration_days' => null,
    ]);

    $this->bronzePlan = SubscriptionPlan::factory()->create([
        'name' => 'Bronze',
        'price' => 99,
        'duration_days' => 30,
    ]);

    $this->goldPlan = SubscriptionPlan::factory()->create([
        'name' => 'Gold',
        'price' => 599,
        'duration_days' => 90,
    ]);

    // Create categories with plan access
    $this->foodCategory = CouponCategory::factory()->create(['name' => 'Food']);
    $this->foodCategory->subscriptionPlans()->attach([$this->basePlan->id, $this->bronzePlan->id, $this->goldPlan->id]);

    $this->premiumCategory = CouponCategory::factory()->create(['name' => 'Premium']);
    $this->premiumCategory->subscriptionPlans()->attach([$this->goldPlan->id]);

    // Create coupons
    $this->foodCoupon = DiscountCoupon::factory()->create([
        'title' => 'Food Coupon',
        'coupon_category_id' => $this->foodCategory->id,
    ]);

    $this->premiumCoupon = DiscountCoupon::factory()->create([
        'title' => 'Premium Coupon',
        'coupon_category_id' => $this->premiumCategory->id,
    ]);
});

test('coupons index shows all coupons with eligibility for gold user', function () {
    $user = User::factory()->create();
    $user->assignRole('User');
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->goldPlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('coupons.index'));

    $response->assertSuccessful();

    $coupons = $response->original->getData()['page']['props']['coupons']['data'];
    foreach ($coupons as $coupon) {
        expect($coupon['is_eligible'])->toBeTrue();
    }
});

test('coupons index marks locked coupons for bronze user', function () {
    $user = User::factory()->create();
    $user->assignRole('User');
    UserSubscription::create([
        'user_id' => $user->id,
        'plan_id' => $this->bronzePlan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('coupons.index'));

    $response->assertSuccessful();

    $coupons = $response->original->getData()['page']['props']['coupons']['data'];
    $premiumCoupon = collect($coupons)->firstWhere('id', $this->premiumCoupon->id);
    $foodCoupon = collect($coupons)->firstWhere('id', $this->foodCoupon->id);

    expect($foodCoupon['is_eligible'])->toBeTrue()
        ->and($premiumCoupon['is_eligible'])->toBeFalse()
        ->and($premiumCoupon['required_plan'])->not->toBeNull()
        ->and($premiumCoupon['required_plan']['name'])->toBe('Gold');
});

test('coupons index shows all coupons as ineligible for guest user', function () {
    $response = $this->get(route('coupons.index'));

    $response->assertSuccessful();

    $coupons = $response->original->getData()['page']['props']['coupons']['data'];
    foreach ($coupons as $coupon) {
        expect($coupon['is_eligible'])->toBeFalse();
    }
});
