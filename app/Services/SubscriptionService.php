<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;

class SubscriptionService
{
    public function upgradePlan(User $user, int $planId): UserSubscription
    {
        // Expire existing active subscriptions
        $user->subscriptions()->where('status', SubscriptionStatus::Active)->update([
            'status' => SubscriptionStatus::Expired,
        ]);

        // Create new subscription
        return UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'status' => SubscriptionStatus::Active,
        ]);
    }

    public function revertToBasePlan(User $user): ?UserSubscription
    {
        $basePlan = SubscriptionPlan::where('is_default', true)->first();

        if (! $basePlan) {
            return null;
        }

        // Expire existing active subscriptions
        $user->subscriptions()->where('status', SubscriptionStatus::Active)->update([
            'status' => SubscriptionStatus::Expired,
        ]);

        return UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $basePlan->id,
            'status' => SubscriptionStatus::Active,
        ]);
    }
}
