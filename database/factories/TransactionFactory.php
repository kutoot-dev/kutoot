<?php

namespace Database\Factories;

use App\Models\MerchantLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalBill = fake()->randomFloat(2, 100, 1000);
        $discount = fake()->randomFloat(2, 10, min(100, $originalBill));
        $amount = max(0, $originalBill - $discount);
        $platformFee = 10.00;
        $gst = round($platformFee * 0.18, 2);

        return [
            'user_id' => User::factory(),
            'merchant_location_id' => MerchantLocation::factory(),
            'original_bill_amount' => $originalBill,
            'discount_amount' => $discount,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'gst_amount' => $gst,
            'total_amount' => $amount + $platformFee + $gst,
            'commission_amount' => round($amount * 0.05, 2),
        ];
    }
}
