<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Silver', 'Gold', 'Platinum']),
            'max_discounted_bills' => fake()->numberBetween(5, 50),
            'max_redeemable_amount' => fake()->randomFloat(2, 500, 10000),
            'max_concurrent_campaigns_per_bill' => fake()->numberBetween(1, 5),
        ];
    }
}
