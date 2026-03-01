<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionType;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;

class SubscriptionService
{
    public function __construct(
        protected StampService $stampService,
        protected CampaignSubscriptionService $campaignSubscriptionService,
    ) {}

    /**
     * Upgrade (or purchase) a plan for the user.
     * Expires existing active subscriptions and creates a new one.
     * Reconciles campaign subscriptions and auto-subscribes to new campaigns.
     * If a paid transaction is provided (from Razorpay checkout), use it instead of creating a new one.
     *
     * @param  array<int, array{campaign_id: int, stamp_count?: int}>  $campaignSelections
     */
    public function upgradePlan(User $user, int $planId, ?Transaction $paidTransaction = null, array $campaignSelections = []): UserSubscription
    {
        // Expire existing active subscriptions
        $user->subscriptions()->where('status', SubscriptionStatus::Active)->update([
            'status' => SubscriptionStatus::Expired,
        ]);

        // Create new subscription
        $plan = SubscriptionPlan::find($planId);
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'status' => SubscriptionStatus::Active,
            'expires_at' => $plan?->duration_days ? now()->addDays($plan->duration_days) : null,
        ]);

        // Use existing paid transaction or create a record for free plan upgrades
        if ($paidTransaction) {
            $paidTransaction->update(['payment_status' => PaymentStatus::Completed]);
            $transaction = $paidTransaction;
        } else {
            $planPrice = (float) ($plan?->price ?? 0);
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $planPrice,
                'original_bill_amount' => $planPrice,
                'total_amount' => $planPrice,
                'payment_status' => PaymentStatus::Completed,
                'payment_gateway' => 'plan_upgrade',
                'payment_id' => 'PLAN-'.$planId.'-'.now()->timestamp,
                'type' => TransactionType::PlanPurchase,
                'commission_amount' => 0,
            ]);
        }

        // Reconcile campaign subscriptions for the new plan
        if ($plan) {
            $this->campaignSubscriptionService->reconcileAfterPlanChange($user, $plan);
            $this->campaignSubscriptionService->autoSubscribeForPlan($user, $plan);
            $user->refresh();
        }

        // If campaign selections were provided, set the first one as primary
        if (! empty($campaignSelections) && $plan) {
            $primaryCampaignId = $campaignSelections[0]['campaign_id'] ?? null;
            if ($primaryCampaignId) {
                $isAccessible = $plan->campaigns()->where('campaigns.id', $primaryCampaignId)->exists();
                if ($isAccessible) {
                    if (! $user->isSubscribedToCampaign($primaryCampaignId)) {
                        // Direct attach without full eligibility validation since
                        // we're inside a plan upgrade and already verified plan access.
                        $hasPrimary = $user->campaigns()->wherePivot('is_primary', true)->exists();
                        $user->campaigns()->attach($primaryCampaignId, [
                            'is_primary' => ! $hasPrimary,
                            'subscribed_at' => now(),
                        ]);
                        if (! $hasPrimary) {
                            $user->update(['primary_campaign_id' => $primaryCampaignId]);
                        }
                    }
                    $this->campaignSubscriptionService->setPrimary($user, $primaryCampaignId);
                    $user->refresh();
                }
            }
        }

        if ($plan && $plan->stamps_on_purchase > 0 && $user->primary_campaign_id) {
            $this->stampService->awardStampsForPlanPurchase($user, $plan, transaction: $transaction);
        }

        return $subscription;
    }

    /**
     * Set the user's primary campaign (must be subscribed and accessible from their current plan).
     * Auto-subscribes the user if not already subscribed.
     */
    public function setPrimaryCampaign(User $user, int $campaignId): bool
    {
        $subscription = $user->effectiveSubscription();

        if (! $subscription) {
            return false;
        }

        $plan = SubscriptionPlan::find($subscription->plan_id);

        if (! $plan) {
            return false;
        }

        // Verify the campaign is accessible under the user's plan
        $isAccessible = $plan->campaigns()->where('campaigns.id', $campaignId)->exists();

        if (! $isAccessible) {
            return false;
        }

        // Auto-subscribe if not already subscribed
        if (! $user->isSubscribedToCampaign($campaignId)) {
            $this->campaignSubscriptionService->subscribe($user, $campaignId);
        }

        // Set as primary
        $this->campaignSubscriptionService->setPrimary($user, $campaignId);

        // Award plan purchase stamps if this is a first-time campaign selection after plan purchase
        // and stamps haven't been awarded yet
        if ($plan->stamps_on_purchase > 0) {
            $alreadyAwarded = $user->stamps()
                ->where('campaign_id', $campaignId)
                ->where('source', 'plan_purchase')
                ->exists();

            if (! $alreadyAwarded) {
                // Reuse the transaction created during plan upgrade instead of creating a duplicate
                $transaction = $user->transactions()
                    ->where('payment_gateway', 'plan_upgrade')
                    ->where('payment_id', 'like', 'PLAN-'.$plan->id.'-%')
                    ->latest()
                    ->first();

                if (! $transaction) {
                    $transaction = Transaction::create([
                        'user_id' => $user->id,
                        'amount' => (float) ($plan->price ?? 0),
                        'original_bill_amount' => (float) ($plan->price ?? 0),
                        'total_amount' => (float) ($plan->price ?? 0),
                        'payment_status' => PaymentStatus::Completed,
                        'payment_gateway' => 'plan_upgrade',
                        'payment_id' => 'PLAN-'.$plan->id.'-'.now()->timestamp,
                        'type' => TransactionType::PlanPurchase,
                        'commission_amount' => 0,
                    ]);
                }

                $this->stampService->awardStampsForPlanPurchase($user, $plan, $campaignId, $transaction);
            }
        }

        return true;
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

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $basePlan->id,
            'status' => SubscriptionStatus::Active,
            'expires_at' => null,
        ]);

        // Reconcile campaign subscriptions for the base plan
        $this->campaignSubscriptionService->reconcileAfterPlanChange($user, $basePlan);

        return $subscription;
    }
}
