<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionType;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(
        protected StampService $stampService,
        protected CampaignSubscriptionService $campaignSubscriptionService,
    ) {}

    /**
     * Calculate days remaining for a subscription based on its expiry date.
     */
    public function calculateDaysRemaining(?\Carbon\CarbonInterface $expiresAt): ?int
    {
        if (! $expiresAt) {
            return null;
        }

        return (int) max(0, now()->diffInDays($expiresAt, false));
    }

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
        Log::info('upgradePlan called', ['user_id' => $user->id, 'plan_id' => $planId, 'existing_active' => $user->subscriptions()->where('status', SubscriptionStatus::Active)->count()]);

        // Expire existing active subscriptions
        $expired = $user->subscriptions()->where('status', SubscriptionStatus::Active)->update([
            'status' => SubscriptionStatus::Expired,
        ]);
        Log::info('upgradePlan expired existing subscriptions', ['user_id' => $user->id, 'expired_count' => $expired]);

        // Create new subscription
        $plan = SubscriptionPlan::find($planId);
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'status' => SubscriptionStatus::Active,
            'expires_at' => $plan?->duration_days ? now()->addDays($plan->duration_days) : null,
        ]);

        // Use existing paid transaction or create a record — skip entirely for default plans
        $transaction = null;
        if ($plan?->is_default) {
            // Default plan — no transaction needed
            Log::info('upgradePlan skipping transaction for default plan', ['user_id' => $user->id, 'plan_id' => $planId]);
        } elseif ($paidTransaction) {
            $paidTransaction->update(['payment_status' => PaymentStatus::Completed]);
            Log::info('Using existing paid transaction for plan upgrade', ['transaction_id' => $paidTransaction->id, 'user_id' => $user->id]);
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
            Log::info('Created transaction for plan upgrade', ['transaction_id' => $transaction->id, 'user_id' => $user->id, 'plan_id' => $planId, 'amount' => $planPrice]);
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
            Log::info('Awarding stamps for plan purchase during upgradePlan', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'stamps_on_purchase' => $plan->stamps_on_purchase,
                'primary_campaign_id' => $user->primary_campaign_id,
            ]);
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
        Log::info('setPrimaryCampaign called', ['user_id' => $user->id, 'campaign_id' => $campaignId]);
        $subscription = $user->effectiveSubscription();
        Log::debug('setPrimaryCampaign effective subscription', ['user_id' => $user->id, 'subscription' => $subscription?->toArray()]);

        if (! $subscription) {
            Log::warning('setPrimaryCampaign aborted: no subscription', ['user_id' => $user->id]);
            return false;
        }

        $plan = SubscriptionPlan::find($subscription->plan_id);

        if (! $plan) {
            Log::warning('setPrimaryCampaign aborted: plan not found for subscription', ['user_id' => $user->id, 'subscription_id' => $subscription->id]);
            return false;
        }

        // Verify the campaign is accessible under the user's plan
        $isAccessible = $plan->campaigns()->where('campaigns.id', $campaignId)->exists();

        if (! $isAccessible) {
            Log::warning('setPrimaryCampaign aborted: campaign not accessible under plan', ['user_id' => $user->id, 'campaign_id' => $campaignId, 'plan_id' => $plan->id]);
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
            Log::info('plan has stamps_on_purchase; checking whether to award', ['user_id' => $user->id, 'plan_id' => $plan->id, 'campaign_id' => $campaignId]);
            $alreadyAwarded = $user->stamps()
                ->where('campaign_id', $campaignId)
                ->where('source', 'plan_purchase')
                ->exists();

            if (! $alreadyAwarded) {
                $transaction = null;

                // Only create/reuse a transaction for non-default (paid) plans
                if (! $plan->is_default) {
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
                }

                $this->stampService->awardStampsForPlanPurchase($user, $plan, $campaignId, $transaction);
            }
        }

        return true;
    }

    /**
     * Revert user to the base (default) plan.
     *
     * IMPORTANT: Do NOT award stamps here. When a plan expires or is downgraded,
     * the user should not receive stamps_on_purchase for the base plan.
     * Existing stamps from the previous plan remain untouched.
     */
    public function revertToBasePlan(User $user): ?UserSubscription
    {
        Log::info('revertToBasePlan called', ['user_id' => $user->id]);
        $basePlan = SubscriptionPlan::where('is_default', true)->first();

        if (! $basePlan) {
            Log::warning('revertToBasePlan failed: no default plan defined');
            return null;
        }

        // Expire existing active subscriptions
        $count = $user->subscriptions()->where('status', SubscriptionStatus::Active)->update([
            'status' => SubscriptionStatus::Expired,
        ]);
        Log::info('revertToBasePlan expired subscriptions', ['user_id' => $user->id, 'expired_count' => $count]);

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

    /**
     * Record that a user has accepted the terms and conditions for a subscription plan.
     * Uses updateOrCreate to ensure we track the latest acceptance timestamp.
     */
    public function recordTermsAcceptance(User $user, SubscriptionPlan $plan): \App\Models\SubscriptionConsent
    {
        Log::info('recordTermsAcceptance', ['user_id' => $user->id, 'plan_id' => $plan->id]);
        return \App\Models\SubscriptionConsent::updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
            [
                'accepted_at' => now(),
            ]
        );
    }

    /**
     * Assign the default (base) plan to a new user on first login.
     * Awards bonus stamps if the default plan has stamps_on_purchase > 0.
     * Skips if the user already has an active subscription.
     */
    public function assignDefaultPlan(User $user): ?UserSubscription
    {
        Log::info('assignDefaultPlan called', ['user_id' => $user->id]);

        $basePlan = SubscriptionPlan::where('is_default', true)->first();
        if (! $basePlan) {
            Log::warning('assignDefaultPlan no default plan configured');
            return null;
        }

        // If there's an existing active subscription, we only skip when it's *not* the base plan.
        $active = $user->activeSubscription()->first();
        if ($active) {
            if ($active->plan_id !== $basePlan->id) {
                Log::info('assignDefaultPlan skipping because active non-base subscription exists', ['user_id' => $user->id, 'plan_id' => $active->plan_id]);
                return $active;
            }

            // active subscription is already the default plan; ensure campaign subscriptions and
            // primary campaign are set — required for stamp resolution to work.
            $this->campaignSubscriptionService->reconcileAfterPlanChange($user, $basePlan);
            $this->campaignSubscriptionService->autoSubscribeForPlan($user, $basePlan);
            $user->refresh();

            if (! $user->primary_campaign_id) {
                $firstCampaign = $user->campaigns()->first();
                if ($firstCampaign) {
                    $this->campaignSubscriptionService->setPrimary($user, $firstCampaign->id);
                    $user->refresh();
                    Log::info('assignDefaultPlan (existing base plan) set missing primary campaign', ['user_id' => $user->id, 'campaign_id' => $firstCampaign->id]);
                }
            }

            // Ensure bonus stamps have been awarded (may have been missed on first login due to a bug)
            if ($basePlan->stamps_on_purchase > 0) {
                $alreadyAwarded = $user->stamps()
                    ->where('source', 'plan_purchase')
                    ->exists();

                if (! $alreadyAwarded) {
                    Log::info('assignDefaultPlan awarding missing bonus stamps for existing base subscription', ['user_id' => $user->id, 'plan_id' => $basePlan->id, 'stamps_on_purchase' => $basePlan->stamps_on_purchase]);
                    $this->stampService->awardStampsForPlanPurchase($user, $basePlan);
                } else {
                    Log::info('assignDefaultPlan bonus stamps already awarded for existing base subscription — skipping', ['user_id' => $user->id]);
                }
            }

            // User already has the default plan — do not create a new subscription
            return $active;
        }

        // no active subscription – create the default subscription (no expiry for base plan)
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $basePlan->id,
            'status' => SubscriptionStatus::Active,
            'expires_at' => null,
        ]);

        // Auto-subscribe to plan campaigns
        $this->campaignSubscriptionService->reconcileAfterPlanChange($user, $basePlan);
        $this->campaignSubscriptionService->autoSubscribeForPlan($user, $basePlan);
        $user->refresh();
        Log::info('assignDefaultPlan created subscription and reconciled campaigns', ['user_id' => $user->id, 'subscription_id' => $subscription->id, 'plan_id' => $basePlan->id]);

        // Set primary campaign if not already set
        if (! $user->primary_campaign_id) {
            $firstCampaign = $user->campaigns()->first();
            if ($firstCampaign) {
                $this->campaignSubscriptionService->setPrimary($user, $firstCampaign->id);
                $user->refresh();
                Log::info('assignDefaultPlan set initial primary campaign', ['user_id' => $user->id, 'campaign_id' => $firstCampaign->id]);
            }
        }

        // Award bonus stamps for the default plan — only if not already awarded
        if ($basePlan->stamps_on_purchase > 0) {
            $alreadyAwarded = $user->stamps()
                ->where('source', 'plan_purchase')
                ->exists();

            if (! $alreadyAwarded) {
                Log::info('assignDefaultPlan preparing to award bonus stamps', ['user_id' => $user->id, 'plan_id' => $basePlan->id, 'stamps_on_purchase' => $basePlan->stamps_on_purchase, 'primary_campaign_id' => $user->primary_campaign_id]);
                $this->stampService->awardStampsForPlanPurchase($user, $basePlan);
            } else {
                Log::info('assignDefaultPlan skipping stamp award — already awarded for this user', ['user_id' => $user->id]);
            }
        }

        return $subscription;
    }
}
