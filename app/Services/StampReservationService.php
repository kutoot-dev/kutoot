<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\StampSource;
use App\Enums\StampStatus;
use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StampReservationService
{
    public function __construct(
        protected StampService $stampService,
    ) {}

    /**
     * Reserve a stamp for a user on a given campaign.
     *
     * Uses database-level locking to prevent double-allocation:
     * 1. Lock any existing reservation row for this user+campaign.
     * 2. If an active (non-expired) reservation exists, return it (idempotent).
     * 3. Otherwise generate a new stamp with status=Reserved and a 5-minute window.
     *
     * @throws InvalidArgumentException If campaign is invalid or user has no access.
     * @throws RuntimeException         If stamp code generation fails.
     */
    public function reserve(User $user, int $campaignId): Stamp
    {
        return DB::transaction(function () use ($user, $campaignId): Stamp {
            // ── Validate campaign ───────────────────────────────────
            $campaign = Campaign::where('id', $campaignId)
                ->where('is_active', true)
                ->where('status', CampaignStatus::Active)
                ->first();

            if (! $campaign) {
                throw new InvalidArgumentException('Campaign not found or is no longer active.');
            }

            // ── Validate user access to this campaign ───────────────
            if ($user->campaigns()->exists() && ! $user->isSubscribedToCampaign($campaignId)) {
                throw new InvalidArgumentException('You do not have access to this campaign under your current plan.');
            }

            // ── Lock existing reservations (prevents race conditions) ─
            $existingReservation = Stamp::where('user_id', $user->id)
                ->where('campaign_id', $campaignId)
                ->where('status', StampStatus::Reserved)
                ->lockForUpdate()
                ->first();

            // Return active reservation if it hasn't expired (idempotent)
            if ($existingReservation && $existingReservation->expires_at->isFuture()) {
                return $existingReservation->load('campaign');
            }

            // Expire the stale reservation if it's past its window
            if ($existingReservation && $existingReservation->expires_at->isPast()) {
                $existingReservation->update(['status' => StampStatus::Expired]);
            }

            // ── Generate a unique stamp code ────────────────────────
            $code = $this->stampService->generateUniqueStampCode($campaign);

            if (! $code) {
                throw new RuntimeException('Unable to generate a unique stamp code. Please try again.');
            }

            $expiresAt = now()->addMinutes(
                (int) config('services.stamps.reservation_duration_minutes', 5)
            );

            // ── Create the reserved stamp ───────────────────────────
            $stamp = Stamp::create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'code' => $code,
                'source' => StampSource::PlanPurchase,
                'status' => StampStatus::Reserved,
                'reserved_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            return $stamp->load('campaign');
        });
    }

    /**
     * Confirm a reserved stamp after successful payment.
     *
     * Transitions the stamp from Reserved → Used and attaches the transaction.
     *
     * @throws InvalidArgumentException If the stamp is not in a confirmable state.
     */
    public function confirm(Stamp $stamp, Transaction $transaction): Stamp
    {
        if ($stamp->status !== StampStatus::Reserved) {
            throw new InvalidArgumentException('This stamp is not in a reserved state.');
        }

        if ($stamp->expires_at && $stamp->expires_at->isPast()) {
            $stamp->update(['status' => StampStatus::Expired]);

            throw new InvalidArgumentException('This reservation has expired. Please reserve a new stamp.');
        }

        $isEditable = $this->isEditableForCampaign($stamp);
        $editableUntil = $isEditable
            ? now()->addMinutes((int) config('services.stamps.edit_duration_minutes', 15))
            : null;

        $stamp->update([
            'status' => StampStatus::Used,
            'transaction_id' => $transaction->id,
            'editable_until' => $editableUntil,
        ]);

        // Increment the campaign's stamp cache
        $stamp->campaign?->increment('issued_stamps_cache');

        return $stamp->fresh()->load('campaign', 'transaction');
    }

    /**
     * Cancel / release a reservation before it expires.
     *
     * @throws InvalidArgumentException If stamp is not reserved.
     */
    public function cancel(Stamp $stamp): void
    {
        if ($stamp->status !== StampStatus::Reserved) {
            throw new InvalidArgumentException('Only reserved stamps can be cancelled.');
        }

        $stamp->update(['status' => StampStatus::Expired]);
    }

    /**
     * Fetch the user's active (non-expired) reservation for a campaign.
     * Also considers expired-at-query-time stamps (belt-and-suspenders).
     */
    public function getActiveReservation(User $user, int $campaignId): ?Stamp
    {
        return Stamp::where('user_id', $user->id)
            ->where('campaign_id', $campaignId)
            ->where('status', StampStatus::Reserved)
            ->where('expires_at', '>', now())
            ->with('campaign')
            ->first();
    }

    /**
     * Bulk-expire all reservations that have passed their expiry window.
     *
     * Called by the scheduled artisan command `stamps:release-expired`.
     */
    public function releaseExpired(): int
    {
        return Stamp::where('status', StampStatus::Reserved)
            ->where('expires_at', '<=', now())
            ->update(['status' => StampStatus::Expired]);
    }

    /**
     * Determine if the stamp should be editable based on its campaign config & source.
     */
    protected function isEditableForCampaign(Stamp $stamp): bool
    {
        $campaign = $stamp->campaign;

        if (! $campaign) {
            return false;
        }

        return match ($stamp->source) {
            StampSource::PlanPurchase => (bool) $campaign->stamp_editable_on_plan_purchase,
            StampSource::CouponRedemption => (bool) $campaign->stamp_editable_on_coupon_redemption,
            default => false,
        };
    }
}
