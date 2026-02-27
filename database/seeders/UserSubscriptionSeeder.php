<?php

namespace Database\Seeders;

use App\Models\UserSubscription;
use Illuminate\Database\Seeder;

class UserSubscriptionSeeder extends Seeder
{
    /**
     * Seed sample user subscriptions with mixed states.
     */
    public function run(): void
    {
        UserSubscription::factory()->count(4)->create();
        UserSubscription::factory()->count(2)->expired()->create();
    }
}
