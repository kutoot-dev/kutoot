<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CouponCategory
 */
class CouponCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->whenHas('slug'),
            'icon' => $this->icon,
            'is_active' => $this->whenHas('is_active'),
            'coupons_count' => $this->whenCounted('coupons'),
            'subscription_plans' => SubscriptionPlanResource::collection($this->whenLoaded('subscriptionPlans')),
            'created_at' => $this->whenHas('created_at', fn () => $this->created_at?->toISOString()),
            'updated_at' => $this->whenHas('updated_at', fn () => $this->updated_at?->toISOString()),
        ];
    }
}
