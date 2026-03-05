<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SubscriptionPlan
 */
class SubscriptionPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'best_value' => (bool) $this->best_value,
            'price' => (float) $this->price,
            'original_price' => $this->original_price ? (float) $this->original_price : null,
            'is_default' => $this->is_default,
            'stamps_on_purchase' => $this->stamps_on_purchase,
            'stamp_denomination' => (float) $this->stamp_denomination,
            'stamps_per_denomination' => $this->stamps_per_denomination,
            'max_discounted_bills' => $this->max_discounted_bills,
            'max_redeemable_amount' => (float) $this->max_redeemable_amount,
            'duration_days' => $this->duration_days,
            'campaigns' => CampaignResource::collection($this->whenLoaded('campaigns')),
            'coupon_categories' => CouponCategoryResource::collection($this->whenLoaded('couponCategories')),
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
