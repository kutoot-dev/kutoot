<?php

namespace App\Listeners;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Auth\Events\Registered;

class AssignBasePlanListener
{
    /**
     * Assign the default (base) plan to newly registered users.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        $basePlan = SubscriptionPlan::where('is_default', true)->first();

        if (! $basePlan) {
            return;
        }

        UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $basePlan->id,
            'status' => SubscriptionStatus::Active,
            'expires_at' => null,
        ]);
    }
}
