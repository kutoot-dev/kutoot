<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'country_id' => $this->country_id,
            'country_name' => $this->whenLoaded('country', fn () => $this->country?->name),
            'state_id' => $this->state_id,
            'state_name' => $this->whenLoaded('state', fn () => $this->state?->name),
            'city_id' => $this->city_id,
            'city_name' => $this->whenLoaded('city', fn () => $this->city?->name),
            'pin_code' => $this->pin_code,
            'full_address' => $this->full_address,
            'profile_picture_url' => $this->profile_picture_url,
            'primary_campaign_id' => $this->primary_campaign_id,
            'primary_campaign' => new CampaignResource($this->whenLoaded('primaryCampaign')),
            'active_subscription' => new UserSubscriptionResource($this->whenLoaded('activeSubscription')),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
