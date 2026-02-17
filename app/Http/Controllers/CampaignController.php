<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\BountyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct(protected BountyService $bountyService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $planId = $user?->activeSubscription?->plan_id;

        $campaigns = Campaign::query()
            ->when($planId, fn ($q) => $q->forPlan($planId))
            ->active()
            ->with(['category', 'creator.merchantLocations.merchant'])
            ->latest()
            ->paginate(9);

        // Transform to include merchant info and bounty percentage
        $campaigns->through(function (Campaign $campaign) {
            $merchant = $campaign->creator?->merchant;
            $data = $campaign->toArray();
            $data['creator'] = array_merge($campaign->creator?->toArray() ?? [], [
                'merchant' => $merchant ? [
                    'name' => $merchant->name,
                    'logo' => $merchant->logo,
                ] : null,
            ]);
            $data['bounty_percentage'] = $this->bountyService->effectiveBountyPercentage($campaign);

            return $data;
        });

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['category', 'stamps', 'creator.merchantLocations.merchant']);

        $merchant = $campaign->creator?->merchant;
        $bountyPercentage = $this->bountyService->effectiveBountyPercentage($campaign);

        return Inertia::render('Campaigns/Show', [
            'campaign' => array_merge($campaign->toArray(), [
                'creator' => array_merge($campaign->creator?->toArray() ?? [], [
                    'merchant' => $merchant ? [
                        'name' => $merchant->name,
                        'logo' => $merchant->logo,
                    ] : null,
                ]),
            ]),
            'bountyPercentage' => $bountyPercentage,
            'collectedCommission' => $campaign->collected_commission_cache,
            'issuedStamps' => $campaign->issued_stamps_cache,
        ]);
    }
}
