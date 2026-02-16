<?php

namespace Database\Factories;

use App\Models\CouponRedemption;
use App\Models\DiscountCoupon;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coupon_id' => DiscountCoupon::factory(),
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'discount_applied' => fake()->randomFloat(2, 10, 500),
        ];
    }
}
