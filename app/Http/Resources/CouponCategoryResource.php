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
            'slug' => $this->resource->getAttributes()['slug'] ?? null,
            'icon' => $this->resource->getAttributes()['icon'] ?? null,
            'is_active' => $this->resource->getAttributes()['is_active'] ?? true,
            'coupons_count' => $this->whenCounted('coupons'),
            'subscription_plans' => SubscriptionPlanResource::collection($this->whenLoaded('subscriptionPlans')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
