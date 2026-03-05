<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Campaign
 */
class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'reward_name' => $this->reward_name,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'category' => new CampaignCategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'stamp_config' => $this->when($this->hasStampConfig(), fn () => [
                'code' => $this->code,
                'slots' => $this->stamp_slots,
                'min' => $this->stamp_slot_min,
                'max' => $this->stamp_slot_max,
                'editable_on_plan_purchase' => $this->stamp_editable_on_plan_purchase,
                'editable_on_coupon_redemption' => $this->stamp_editable_on_coupon_redemption,
                'sample_code' => $this->generateSampleStampCode(),
                'possible_combinations' => $this->getPossibleCombinations(),
            ]),
            'reward_cost_target' => (float) $this->reward_cost_target,
            'stamp_target' => $this->stamp_target,
            'marketing_bounty_percentage' => $this->marketing_bounty_percentage,
            'collected_commission_cache' => (float) $this->collected_commission_cache,
            'issued_stamps_cache' => $this->issued_stamps_cache,
            'winner_announcement_date' => $this->winner_announcement_date?->toISOString(),
            'is_active' => $this->is_active,
            'is_premium' => $this->is_premium,
            'bounty_percentage' => $this->whenHas('bounty_percentage'),
            'is_eligible' => $this->whenHas('is_eligible'),
            'required_plan' => $this->whenHas('required_plan'),
            'media' => $this->whenLoaded('media', fn () => $this->getMedia('media')->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
                'preview' => $m->getUrl('preview'),
                'name' => $m->name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
            ])),
            'sponsor_image' => $this->when(
                $this->getFirstMedia('sponsor_image'),
                fn () => [
                    'id' => $this->getFirstMedia('sponsor_image')->id,
                    'url' => $this->getFirstMedia('sponsor_image')->getUrl(),
                    'thumb' => $this->getFirstMedia('sponsor_image')->getUrl('sponsor_thumb'),
                    'name' => $this->getFirstMedia('sponsor_image')->name,
                    'mime_type' => $this->getFirstMedia('sponsor_image')->mime_type,
                    'size' => $this->getFirstMedia('sponsor_image')->size,
                ]
            ),
            'plans' => CampaignResource::collection($this->whenLoaded('plans')),
            'stamps_count' => $this->whenCounted('stamps'),
            'subscribers_count' => $this->whenCounted('subscribers'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
