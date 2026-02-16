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
            ->with(['category', 'creator'])
            ->latest()
            ->paginate(9);

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'canLogin' => true,
            'canRegister' => true,
        ]);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['category', 'stamps']);

        $bountyMeter = $this->bountyService->recalculateBountyMeter($campaign);

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'bountyMeter' => $bountyMeter,
            'collectedCommission' => $campaign->collected_commission_cache,
            'issuedStamps' => $campaign->issued_stamps_cache,
        ]);
    }
}
