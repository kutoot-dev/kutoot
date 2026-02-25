<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\BountyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Campaigns
 */
class CampaignController extends Controller
{
    public function __construct(protected BountyService $bountyService) {}

    /**
     * List campaigns
     *
     * Returns a paginated list of active campaigns. When authenticated, includes
     * eligibility info based on the user's subscription plan.
     *
     * @unauthenticated
     *
     * @queryParam category_id int Filter by category ID.
     * @queryParam status string Filter by status (active, closed, completed).
     * @queryParam per_page int Items per page (default: 12, max: 50).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $accessibleCampaignIds = $user ? $user->accessibleCampaignIds() : collect();

        $campaigns = Campaign::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status), fn ($q) => $q->active())
            ->when($request->input('category_id'), fn ($q, $catId) => $q->where('category_id', $catId))
            ->when($request->boolean('premium'), fn ($q) => $q->premium())
            ->with(['category', 'media'])
            ->latest()
            ->paginate(min((int) $request->input('per_page', 12), 50));

        $campaigns->through(function (Campaign $campaign) use ($accessibleCampaignIds) {
            $campaign->setAttribute('bounty_percentage', $this->bountyService->effectiveBountyPercentage($campaign));
            $campaign->setAttribute('is_eligible', $accessibleCampaignIds->contains($campaign->id));

            if (! $accessibleCampaignIds->contains($campaign->id)) {
                $cheapestPlan = $campaign->plans()->orderBy('price', 'asc')->first();
                $campaign->setAttribute('required_plan', $cheapestPlan ? [
                    'name' => $cheapestPlan->name,
                    'price' => (float) $cheapestPlan->price,
                ] : null);
            }

            return $campaign;
        });

        return CampaignResource::collection($campaigns)->additional([
            'meta' => [
                'filters' => [
                    'category_id' => $request->input('category_id'),
                    'status' => $request->input('status'),
                ],
            ],
        ]);
    }

    /**
     * Show campaign
     *
     * Returns detailed information about a specific campaign including stamp configuration.
     *
     * @unauthenticated
     */
    public function show(Campaign $campaign): CampaignResource
    {
        $campaign->load(['category', 'stamps', 'media']);

        $campaign->setAttribute('bounty_percentage', $this->bountyService->effectiveBountyPercentage($campaign));

        return new CampaignResource($campaign);
    }

    /**
     * Campaign bounty
     *
     * Returns the bounty meter data for a campaign including commission progress and stamp progress.
     */
    public function bounty(Campaign $campaign): JsonResponse
    {
        $organicProgress = $this->bountyService->recalculateBountyMeter($campaign);
        $effectivePercentage = $this->bountyService->effectiveBountyPercentage($campaign);

        return response()->json([
            'data' => [
                'campaign_id' => $campaign->id,
                'reward_name' => $campaign->reward_name,
                'bounty_percentage' => $effectivePercentage,
                'organic_progress' => round($organicProgress * 100, 2),
                'marketing_boost' => $campaign->marketing_bounty_percentage ?? 0,
                'collected_commission' => (float) $campaign->collected_commission_cache,
                'reward_cost_target' => (float) $campaign->reward_cost_target,
                'issued_stamps' => (int) $campaign->issued_stamps_cache,
                'stamp_target' => (int) $campaign->stamp_target,
                'status' => $campaign->status,
            ],
        ]);
    }
}
