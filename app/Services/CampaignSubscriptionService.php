<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CampaignSubscriptionService
{
    /**
     * Subscribe a user to a campaign. Validates plan access and prevents duplicates.
     *
     * @throws InvalidArgumentException
     */
    public function subscribe(User $user, int $campaignId): void
    {
        $campaign = Campaign::findOrFail($campaignId);

        $this->validateCampaignEligibility($user, $campaign);

        if ($user->isSubscribedToCampaign($campaignId)) {
            throw new InvalidArgumentException('You are already subscribed to this campaign.');
        }

        $hasPrimary = $user->campaigns()->wherePivot('is_primary', true)->exists();

        DB::transaction(function () use ($user, $campaignId, $hasPrimary) {
            $user->campaigns()->attach($campaignId, [
                'is_primary' => ! $hasPrimary,
                'subscribed_at' => now(),
            ]);

            // If this is the first campaign (set as primary), sync primary_campaign_id
            if (! $hasPrimary) {
                $user->update(['primary_campaign_id' => $campaignId]);
            }
        });
    }

    /**
     * Unsubscribe a user from a campaign. Auto-promotes next campaign if primary is removed.
     *
     * @throws InvalidArgumentException
     */
    public function unsubscribe(User $user, int $campaignId): void
    {
        if (! $user->isSubscribedToCampaign($campaignId)) {
            throw new InvalidArgumentException('You are not subscribed to this campaign.');
        }

        $wasPrimary = $user->campaigns()
            ->where('campaigns.id', $campaignId)
            ->wherePivot('is_primary', true)
            ->exists();

        DB::transaction(function () use ($user, $campaignId, $wasPrimary) {
            $user->campaigns()->detach($campaignId);

            if ($wasPrimary) {
                $this->promoteNextPrimary($user);
            }
        });
    }

    /**
     * Set a specific campaign as the user's primary. Must be subscribed and plan-accessible.
     *
     * @throws InvalidArgumentException
     */
    public function setPrimary(User $user, int $campaignId): void
    {
        if (! $user->isSubscribedToCampaign($campaignId)) {
            throw new InvalidArgumentException('You must be subscribed to a campaign before setting it as primary.');
        }

        $accessibleIds = $user->accessibleCampaignIds();
        if (! $accessibleIds->contains($campaignId)) {
            throw new InvalidArgumentException('This campaign is not accessible under your current plan.');
        }

        DB::transaction(function () use ($user, $campaignId) {
            // Clear existing primary
            $user->campaigns()->updateExistingPivot(
                $user->campaigns()->wherePivot('is_primary', true)->pluck('campaigns.id')->toArray(),
                ['is_primary' => false],
            );

            // Set new primary
            $user->campaigns()->updateExistingPivot($campaignId, ['is_primary' => true]);
            $user->update(['primary_campaign_id' => $campaignId]);
        });
    }

    /**
     * Reconcile campaign subscriptions after a plan change.
     * Removes subscriptions to campaigns no longer accessible, auto-promotes a new primary if needed.
     *
     * @return array{removed: list<int>, kept: list<int>}
     */
    public function reconcileAfterPlanChange(User $user, SubscriptionPlan $newPlan): array
    {
        $accessibleCampaignIds = $newPlan->campaigns()->pluck('campaigns.id');
        $subscribedCampaignIds = $user->campaigns()->pluck('campaigns.id');

        $toRemove = $subscribedCampaignIds->diff($accessibleCampaignIds);
        $toKeep = $subscribedCampaignIds->intersect($accessibleCampaignIds);

        if ($toRemove->isNotEmpty()) {
            DB::transaction(function () use ($user, $toRemove) {
                $user->campaigns()->detach($toRemove->toArray());
            });
        }

        // If primary was removed, auto-promote the next available
        if ($toRemove->contains($user->primary_campaign_id)) {
            $this->promoteNextPrimary($user);
        }

        return [
            'removed' => $toRemove->values()->all(),
            'kept' => $toKeep->values()->all(),
        ];
    }

    /**
     * Auto-subscribe user to all campaigns accessible under their plan.
     * Useful when upgrading to a higher plan.
     *
     * @return list<int> Campaign IDs newly subscribed
     */
    public function autoSubscribeForPlan(User $user, SubscriptionPlan $plan): array
    {
        $accessibleCampaignIds = $plan->campaigns()
            ->where('is_active', true)
            ->where('status', CampaignStatus::Active)
            ->pluck('campaigns.id');

        $alreadySubscribed = $user->campaigns()->pluck('campaigns.id');
        $newCampaignIds = $accessibleCampaignIds->diff($alreadySubscribed);

        if ($newCampaignIds->isEmpty()) {
            return [];
        }

        $hasPrimary = $user->campaigns()->wherePivot('is_primary', true)->exists();
        $attachData = [];

        foreach ($newCampaignIds as $index => $campaignId) {
            $attachData[$campaignId] = [
                'is_primary' => ! $hasPrimary && $index === 0,
                'subscribed_at' => now(),
            ];
        }

        DB::transaction(function () use ($user, $attachData, $hasPrimary, $newCampaignIds) {
            $user->campaigns()->attach($attachData);

            // Set primary_campaign_id if first-time
            if (! $hasPrimary && $newCampaignIds->isNotEmpty()) {
                $user->update(['primary_campaign_id' => $newCampaignIds->first()]);
            }
        });

        return $newCampaignIds->values()->all();
    }

    /**
     * Get campaigns the user is eligible for but not yet subscribed to.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Campaign>
     */
    public function getAvailableCampaigns(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $accessibleIds = $user->accessibleCampaignIds();
        $subscribedIds = $user->campaigns()->pluck('campaigns.id');
        $unsubscribedIds = $accessibleIds->diff($subscribedIds);

        return Campaign::whereIn('id', $unsubscribedIds)
            ->where('is_active', true)
            ->where('status', CampaignStatus::Active)
            ->get();
    }

    /**
     * Get campaigns NOT accessible under the user's plan, with the minimum plan required.
     *
     * @return \Illuminate\Support\Collection<int, array{campaign: Campaign, required_plan: SubscriptionPlan}>
     */
    public function getLockedCampaigns(User $user): \Illuminate\Support\Collection
    {
        $accessibleIds = $user->accessibleCampaignIds();

        $lockedCampaigns = Campaign::whereNotIn('id', $accessibleIds)
            ->where('is_active', true)
            ->where('status', CampaignStatus::Active)
            ->with('plans')
            ->get();

        return $lockedCampaigns->map(function (Campaign $campaign) {
            // Find the cheapest plan that gives access
            $cheapestPlan = $campaign->plans()
                ->orderBy('price', 'asc')
                ->first();

            return [
                'campaign' => $campaign,
                'required_plan' => $cheapestPlan,
            ];
        });
    }

    /**
     * Promote the next available campaign to primary status after the current primary is removed.
     */
    protected function promoteNextPrimary(User $user): void
    {
        $nextCampaign = $user->campaigns()
            ->wherePivot('is_primary', false)
            ->first();

        if ($nextCampaign) {
            $user->campaigns()->updateExistingPivot($nextCampaign->id, ['is_primary' => true]);
            $user->update(['primary_campaign_id' => $nextCampaign->id]);
        } else {
            $user->update(['primary_campaign_id' => null]);
        }
    }

    /**
     * Validate that a campaign is eligible for subscription.
     *
     * @throws InvalidArgumentException
     */
    protected function validateCampaignEligibility(User $user, Campaign $campaign): void
    {
        if (! $campaign->is_active) {
            throw new InvalidArgumentException('This campaign is not currently active.');
        }

        if ($campaign->status !== CampaignStatus::Active) {
            throw new InvalidArgumentException('This campaign is '.$campaign->status->value.' and cannot accept new subscribers.');
        }

        // Check if campaign is accessible under user's plan
        $accessibleIds = $user->accessibleCampaignIds();
        if (! $accessibleIds->contains($campaign->id)) {
            $cheapestPlan = $campaign->plans()->orderBy('price', 'asc')->first();
            $planName = $cheapestPlan?->name ?? 'a higher';

            throw new InvalidArgumentException("This campaign requires the {$planName} plan or above. Please upgrade to access it.");
        }

        // Check if campaign has reached its stamp target (completed)
        if ($campaign->issued_stamps_cache >= $campaign->stamp_target) {
            throw new InvalidArgumentException('This campaign has reached its stamp target and is no longer accepting subscribers.');
        }
    }
}
