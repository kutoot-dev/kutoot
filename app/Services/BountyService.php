<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Models\Campaign;

class BountyService
{
    /**
     * Calculate the organic bounty progress based on commission and stamp targets.
     */
    public function recalculateBountyMeter(Campaign $campaign): float
    {
        $costTarget = $campaign->reward_cost_target ?? 1.0;
        $stampTarget = $campaign->stamp_target ?? 1;

        $commissionProgress = ($campaign->collected_commission_cache / $costTarget) * 0.66;
        $stampProgress = ($campaign->issued_stamps_cache / $stampTarget) * 0.33;

        $totalProgress = $commissionProgress + $stampProgress;

        return min(1.0, (float) $totalProgress);
    }

    /**
     * Calculate the effective bounty percentage combining organic progress + marketing boost.
     * The marketing percentage is added as a base so campaigns never appear at 0%.
     */
    public function effectiveBountyPercentage(Campaign $campaign): int
    {
        $organicProgress = $this->recalculateBountyMeter($campaign);
        $organicPercentage = (int) round($organicProgress * 100);
        $marketingBoost = $campaign->marketing_bounty_percentage ?? 0;

        return min(100, $organicPercentage + $marketingBoost);
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
