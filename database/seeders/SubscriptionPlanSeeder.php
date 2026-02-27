<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed subscription plans with progressive tiers.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Bronze',
                'price' => 99,
                'stamps_on_purchase' => 2,
                'stamp_denomination' => 100,
                'stamps_per_denomination' => 1,
                'max_discounted_bills' => 5,
                'max_redeemable_amount' => 500,
                'duration_days' => 30,
            ],
            [
                'name' => 'Silver',
                'price' => 299,
                'stamps_on_purchase' => 5,
                'stamp_denomination' => 50,
                'stamps_per_denomination' => 2,
                'max_discounted_bills' => 15,
                'max_redeemable_amount' => 1500,
                'duration_days' => 60,
            ],
            [
                'name' => 'Gold',
                'price' => 599,
                'stamps_on_purchase' => 10,
                'stamp_denomination' => 25,
                'stamps_per_denomination' => 3,
                'max_discounted_bills' => 30,
                'max_redeemable_amount' => 3000,
                'duration_days' => 90,
            ],
            [
                'name' => 'Platinum',
                'price' => 999,
                'stamps_on_purchase' => 15,
                'stamp_denomination' => 15,
                'stamps_per_denomination' => 5,
                'max_discounted_bills' => 50,
                'max_redeemable_amount' => 5000,
                'duration_days' => 180,
            ],
            [
                'name' => 'Diamond',
                'price' => 1999,
                'stamps_on_purchase' => 25,
                'stamp_denomination' => 10,
                'stamps_per_denomination' => 8,
                'max_discounted_bills' => 100,
                'max_redeemable_amount' => 10000,
                'duration_days' => 365,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['name' => $plan['name']],
                $plan,
            );
        }
    }
}
