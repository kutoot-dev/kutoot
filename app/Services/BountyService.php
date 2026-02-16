<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Models\Campaign;

class BountyService
{
    public function recalculateBountyMeter(Campaign $campaign): float
    {
        $costTarget = $campaign->reward_cost_target ?? 1.0;
        $stampTarget = $campaign->stamp_target ?? 1;

        $commissionProgress = ($campaign->collected_commission_cache / $costTarget) * 0.66;
        $stampProgress = ($campaign->issued_stamps_cache / $stampTarget) * 0.33;

        $totalProgress = $commissionProgress + $stampProgress;

        return min(1.0, (float) $totalProgress);
    }

    public function onCommissionEarned(Campaign $campaign, float $amount): void
    {
        $campaign->collected_commission_cache += $amount;
        $campaign->save();

        $this->updateCampaignStatus($campaign);
    }

    public function onStampsIssued(Campaign $campaign, int $count): void
    {
        $campaign->issued_stamps_cache += $count;
        $campaign->save();

        $this->updateCampaignStatus($campaign);
    }

    protected function updateCampaignStatus(Campaign $campaign): void
    {
        if ($this->recalculateBountyMeter($campaign) >= 1.0) {
            $campaign->status = CampaignStatus::Completed;
            $campaign->save();
        }
    }
}
