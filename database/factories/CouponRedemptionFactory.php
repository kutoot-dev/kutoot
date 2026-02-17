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
        $originalBill = fake()->randomFloat(2, 100, 1000);
        $discount = fake()->randomFloat(2, 10, min(100, $originalBill));
        $platformFee = 10.00;
        $gst = round($platformFee * 0.18, 2);
        $totalPaid = max(0, $originalBill - $discount) + $platformFee + $gst;

        return [
            'coupon_id' => DiscountCoupon::factory(),
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'discount_applied' => $discount,
            'original_bill_amount' => $originalBill,
            'platform_fee' => $platformFee,
            'gst_amount' => $gst,
            'total_paid' => $totalPaid,
        ];
    }
}
