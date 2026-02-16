<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => SubscriptionStatus::Active,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => SubscriptionStatus::Expired]);
    }
}
