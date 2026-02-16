<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\CouponCategory;
use App\Models\MerchantLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscountCoupon>
 */
class DiscountCouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_location_id' => MerchantLocation::factory(),
            'coupon_category_id' => CouponCategory::factory(),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'code' => strtoupper($this->faker->bothify('??##??')),
            'discount_type' => DiscountType::Fixed,
            'discount_value' => $this->faker->randomFloat(2, 5, 50),
            'starts_at' => now(),
            'is_active' => true,
        ];
    }
}
