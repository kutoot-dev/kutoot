<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Sponsor
 */
class SponsorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'logo' => $this->getFirstMediaUrl('logo', 'preview') ?: null,
            'banner' => $this->getFirstMediaUrl('banner', 'preview') ?: null,
            'link' => $this->link,
        ];
    }
}
