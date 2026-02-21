<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StampController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stamps = $user->stamps()
            ->with(['campaign:id,reward_name,code,stamp_slots,stamp_slot_min,stamp_slot_max', 'transaction:id,amount,original_bill_amount'])
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'source' => $s->source->getLabel(),
                'campaign_id' => $s->campaign_id,
                'campaign_name' => $s->campaign?->reward_name,
                'bill_amount' => (float) ($s->transaction?->original_bill_amount ?: $s->transaction?->amount ?? 0),
                'created_at' => $s->created_at->diffForHumans(),
                'editable_until' => $s->editable_until?->toISOString(),
                'is_editable' => $s->isEditable(),
                'stamp_config' => $s->campaign && $s->campaign->hasStampConfig() ? [
                    'slots' => $s->campaign->stamp_slots,
                    'min' => $s->campaign->stamp_slot_min,
                    'max' => $s->campaign->stamp_slot_max,
                ] : null,
            ]);

        $primaryCampaignId = $user->primary_campaign_id;
        $primaryCampaignName = $user->primaryCampaign?->reward_name;

        // Group stamps by campaign, with primary campaign first
        $grouped = $stamps
            ->groupBy(fn ($s) => $s['campaign_name'] ?? 'No Campaign')
            ->sortKeysUsing(function (string $a, string $b) use ($primaryCampaignName): int {
                if ($a === $primaryCampaignName) {
                    return -1;
                }
                if ($b === $primaryCampaignName) {
                    return 1;
                }

                return strcasecmp($a, $b);
            })
            ->map(fn ($grp) => $grp->values());

        return Inertia::render('Stamps/Index', [
            'stamps' => $stamps,
            'stampGroups' => $grouped,
            'primaryCampaign' => $primaryCampaignName,
            'totalStamps' => $stamps->count(),
        ]);
    }
}
