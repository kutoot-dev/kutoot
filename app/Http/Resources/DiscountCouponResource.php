<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\DiscountCoupon
 */
class DiscountCouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'min_order_value' => (float) $this->min_order_value,
            'max_discount_amount' => $this->max_discount_amount ? (float) $this->max_discount_amount : null,
            'usage_limit' => $this->usage_limit,
            'usage_per_user' => $this->usage_per_user,
            'starts_at' => $this->starts_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_active' => $this->is_active,
            'source' => $this->source,
            'category' => new CouponCategoryResource($this->whenLoaded('category')),
            'merchant_location' => new MerchantLocationResource($this->whenLoaded('merchantLocation')),
            'redemptions_count' => $this->whenCounted('redemptions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Dynamic attributes set by CouponController
        if ($this->resource->getAttributes() !== [] || method_exists($this->resource, 'getAttribute')) {
            if ($this->resource->getAttribute('is_eligible') !== null) {
                $data['is_eligible'] = (bool) $this->resource->getAttribute('is_eligible');
            }
            if ($this->resource->getAttribute('remaining_usage') !== null) {
                $data['remaining_usage'] = (int) $this->resource->getAttribute('remaining_usage');
            }
            if ($this->resource->getAttribute('segment') !== null) {
                $data['segment'] = $this->resource->getAttribute('segment');
            }
            if ($this->resource->getAttribute('required_plan') !== null) {
                $data['required_plan'] = $this->resource->getAttribute('required_plan');
            }
            if ($this->resource->getAttribute('user_redemptions_count') !== null) {
                $data['user_redemptions_count'] = (int) $this->resource->getAttribute('user_redemptions_count');
            }
        }

        return $data;
    }
}
