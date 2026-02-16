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
        return [
            'user_id' => User::factory(),
            'merchant_location_id' => MerchantLocation::factory(),
            'amount' => 100.00,
            'commission_amount' => 10.00,
        ];
    }
}
