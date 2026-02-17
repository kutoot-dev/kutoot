<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Stamp;
use App\Models\Transaction;
use App\Models\User;

class StampService
{
    public function awardStamps(Transaction $transaction, ?int $campaignId = null): void
    {
        $user = $transaction->user;

        // Determine which campaign to award stamps to
        $campaign = $this->resolveCampaign($user, $campaignId);

        if (! $campaign) {
            return;
        }

        // Create the stamp
        Stamp::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'campaign_id' => $campaign->id,
        ]);

        // Increment issued stamps cache if it exists
        $campaign->increment('issued_stamps_cache');
    }

    protected function resolveCampaign(User $user, ?int $campaignId): ?Campaign
    {
        // 1. If explicit campaign ID is provided
        if ($campaignId) {
            return Campaign::find($campaignId);
        }

        // 2. If user has a primary campaign
        if ($user->primary_campaign_id) {
            return $user->primaryCampaign;
        }

        return null;
    }
}
