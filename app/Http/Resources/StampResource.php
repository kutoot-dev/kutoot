<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Stamp
 */
class StampResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'source' => $this->source,
            'status' => $this->status,
            'reserved_at' => $this->reserved_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'remaining_seconds' => $this->remainingSeconds(),
            'is_reserved' => $this->isReserved(),
            'editable_until' => $this->editable_until?->toISOString(),
            'is_editable' => $this->isEditable(),
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'user' => new UserResource($this->whenLoaded('user')),
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
