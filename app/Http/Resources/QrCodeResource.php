<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\QrCode
 */
class QrCodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_code' => $this->unique_code,
            'token' => $this->token,
            'status' => $this->status,
            'is_primary' => $this->is_primary,
            'url' => $this->url,
            'short_url' => $this->short_url,
            'linked_at' => $this->linked_at?->toISOString(),
            'merchant_location' => new MerchantLocationResource($this->whenLoaded('merchantLocation')),
            'executive' => new UserResource($this->whenLoaded('executive')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
