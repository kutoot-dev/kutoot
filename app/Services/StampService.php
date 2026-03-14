<?php

namespace App\Services;

use App\Enums\StampSource;
use App\Enums\StampStatus;
use App\Events\StampsIssued;
use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;


class StampService
{
    /**
     * Award stamps for a bill payment.
     * Number of stamps = floor(original bill amount / denomination) * stamps_per_denomination.
     */
    public function awardStampsForBill(Transaction $transaction, ?int $campaignId = null): int
    {
        $user = $transaction->user;
        Log::info('awardStampsForBill called', [
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'campaign_id' => $campaignId,
            'original_bill_amount' => $transaction->original_bill_amount,
            'amount' => $transaction->amount,
        ]);

        $campaign = $this->resolveCampaign($user, $campaignId);

        if (! $campaign) {
            Log::warning('awardStampsForBill aborted: campaign could not be resolved', ['user_id' => $user->id, 'campaign_id' => $campaignId]);
            return 0;
        }

        $plan = $this->getUserPlan($user);
        $billAmount = (float) ($transaction->original_bill_amount ?: $transaction->amount);
        $stampCount = $plan ? $plan->calculateStampsForAmount($billAmount) : (int) floor($billAmount / 100);

        if ($stampCount <= 0) {
            Log::info('awardStampsForBill calculated zero stamps', ['user_id' => $user->id, 'bill_amount' => $billAmount]);
            return 0;
        }

        Log::info('awardStampsForBill will create stamps', [
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'stamp_count' => $stampCount,
            'plan_id' => $plan?->id,
        ]);

        $this->createStamps($user, $campaign, $stampCount, StampSource::BillPayment, $transaction);

        return $stampCount;
    }

    /**
     * Award bonus stamps when a user purchases or upgrades a plan.
     */
    public function awardStampsForPlanPurchase(User $user, SubscriptionPlan $plan, ?int $campaignId = null, ?Transaction $transaction = null): int
    {
        $campaign = $this->resolveCampaign($user, $campaignId);

        if (! $campaign || $plan->stamps_on_purchase <= 0) {
            return 0;
        }

        $this->createStamps($user, $campaign, $plan->stamps_on_purchase, StampSource::PlanPurchase, $transaction);

        return $plan->stamps_on_purchase;
    }

    /**
     * Award stamps for a coupon redemption with successful bill payment.
     */
    public function awardStampsForCouponRedemption(Transaction $transaction, ?int $campaignId = null): int
    {
        $user = $transaction->user;
        $campaign = $this->resolveCampaign($user, $campaignId);

        if (! $campaign) {
            return 0;
        }

        $plan = $this->getUserPlan($user);
        $billAmount = (float) ($transaction->original_bill_amount ?: $transaction->amount);
        $stampCount = $plan ? $plan->calculateStampsForAmount($billAmount) : (int) floor($billAmount / 100);

        if ($stampCount <= 0) {
            return 0;
        }

        $this->createStamps($user, $campaign, $stampCount, StampSource::CouponRedemption, $transaction);

        return $stampCount;
    }

    /**
     * Award gift/promotional stamps to a user from admin panel.
     * These stamps are NOT editable by the user.
     */
    public function awardGiftStamps(User $user, Campaign $campaign, int $count, ?string $note = 'Gift'): int
    {
        if ($count <= 0) {
            return 0;
        }

        $this->createStamps($user, $campaign, $count, StampSource::AdminGift, null, $note);

        return $count;
    }

    /**
     * Update the stamp code when a user picks their own slot values.
     *
     * @param  array<int>  $slotValues
     *
     * @throws InvalidArgumentException
     */
    public function updateStampCode(Stamp $stamp, array $slotValues): Stamp
    {
        if (! $stamp->isEditable()) {
            throw new InvalidArgumentException('Stamp edit window has expired.');
        }

        $campaign = $stamp->campaign;
        if (! $campaign || ! $campaign->hasStampConfig()) {
            throw new InvalidArgumentException('Campaign does not have stamp code configuration.');
        }

        $this->validateSlotValues($campaign, $slotValues);

        $newCode = $campaign->formatStampCode($slotValues);

        // Check for duplicate code within the same campaign
        $duplicate = Stamp::where('campaign_id', $campaign->id)
            ->where('code', $newCode)
            ->where('id', '!=', $stamp->id)
            ->exists();

        if ($duplicate) {
            throw new InvalidArgumentException('This stamp code combination is already taken in this campaign. Please choose different numbers.');
        }

        $stamp->update(['code' => $newCode]);

        return $stamp->refresh();
    }

    /**
     * Generate strictly ascending random slot values for a campaign.
     *
     * @return array<int>
     */
    public function generateStampSlotValues(Campaign $campaign): array
    {
        if (! $campaign->hasStampConfig()) {
            return [];
        }

        $range = range($campaign->stamp_slot_min, $campaign->stamp_slot_max);
        shuffle($range);

        $selected = array_slice($range, 0, $campaign->stamp_slots);
        sort($selected);

        return $selected;
    }

    /**
     * Create multiple stamp records for a user + campaign.
     */
    protected function createStamps(
        User $user,
        Campaign $campaign,
        int $count,
        StampSource $source,
        ?Transaction $transaction = null,
        ?string $giftNote = null,
    ): void {
        $isEditable = $this->isEditableSource($campaign, $source);
        $editableUntil = $isEditable
            ? now()->addMinutes((int) config('services.stamps.edit_duration_minutes', 15))
            : null;

        for ($i = 0; $i < $count; $i++) {
            $code = $this->generateUniqueStampCode($campaign);

            Stamp::create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'transaction_id' => $transaction?->id,
                'code' => $code,
                'source' => $source,
                'gift_note' => $giftNote,
                'status' => StampStatus::Used,
                'editable_until' => $editableUntil,
            ]);
        }

        $campaign->increment('issued_stamps_cache', $count);

        StampsIssued::dispatch($campaign, $count);
    }

    /**
     * Generate a unique stamp code for a campaign, retrying on collision.
     */
    public function generateUniqueStampCode(Campaign $campaign): string
    {
        if (! $campaign->hasStampConfig()) {
            return 'STP-'.strtoupper(Str::random(8));
        }

        $maxAttempts = 50;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $slotValues = $this->generateStampSlotValues($campaign);
            $code = $campaign->formatStampCode($slotValues);

            $exists = Stamp::where('campaign_id', $campaign->id)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        // Fallback to legacy format if too many collisions
        return 'STP-'.strtoupper(Str::random(8));
    }

    /**
     * Check if stamps from a given source are editable for this campaign.
     */
    protected function isEditableSource(Campaign $campaign, StampSource $source): bool
    {
        return match ($source) {
            StampSource::PlanPurchase => (bool) $campaign->stamp_editable_on_plan_purchase,
            StampSource::CouponRedemption => (bool) $campaign->stamp_editable_on_coupon_redemption,
            StampSource::AdminGift => false, // Gift stamps are not editable
            default => false,
        };
    }

    /**
     * Validate slot values against campaign configuration.
     *
     * @param  array<int>  $slotValues
     *
     * @throws InvalidArgumentException
     */
    protected function validateSlotValues(Campaign $campaign, array $slotValues): void
    {
        if (count($slotValues) !== $campaign->stamp_slots) {
            throw new InvalidArgumentException("Expected {$campaign->stamp_slots} slot values, got ".count($slotValues).'.');
        }

        foreach ($slotValues as $index => $value) {
            if (! is_int($value)) {
                throw new InvalidArgumentException("Slot value at position {$index} must be an integer.");
            }

            if ($value < $campaign->stamp_slot_min || $value > $campaign->stamp_slot_max) {
                throw new InvalidArgumentException("Slot value {$value} is out of range [{$campaign->stamp_slot_min}, {$campaign->stamp_slot_max}].");
            }
        }

        // Verify strictly ascending
        for ($i = 1; $i < count($slotValues); $i++) {
            if ($slotValues[$i] <= $slotValues[$i - 1]) {
                throw new InvalidArgumentException('Slot values must be in strictly ascending order.');
            }
        }
    }

    protected function resolveCampaign(User $user, ?int $campaignId): ?Campaign
    {
        if ($campaignId) {
            $campaign = Campaign::find($campaignId);

            if (! $campaign) {
                return null;
            }

            // Validate campaign is still active
            if (! $campaign->is_active || $campaign->status !== \App\Enums\CampaignStatus::Active) {
                return null;
            }

            // If user has campaign subscriptions, validate they are subscribed to this one
            if ($user->campaigns()->exists() && ! $user->isSubscribedToCampaign($campaignId)) {
                return null;
            }

            return $campaign;
        }

        if ($user->primary_campaign_id) {
            $campaign = $user->primaryCampaign;

            if ($campaign && $campaign->is_active && $campaign->status === \App\Enums\CampaignStatus::Active) {
                return $campaign;
            }
        }

        // Fallback: try to find any subscribed active campaign
        $subscribedCampaign = $user->activeCampaigns()->first();
        if ($subscribedCampaign) {
            return $subscribedCampaign;
        }

        return null;
    }

    protected function getUserPlan(User $user): ?SubscriptionPlan
    {
        $subscription = $user->effectiveSubscription();

        return $subscription?->plan_id ? SubscriptionPlan::find($subscription->plan_id) : null;
    }
}
