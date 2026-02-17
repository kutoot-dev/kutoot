<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class BasePlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['name' => 'Base Plan'],
            [
                'max_discounted_bills' => 5,
                'max_redeemable_amount' => 500,
                'max_concurrent_campaigns_per_bill' => 1,
                'is_default' => true,
            ]
        );
    }
}
