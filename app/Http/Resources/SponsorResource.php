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
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'logo' => $this->getFirstMediaUrl('logo', 'preview') ?: null,
            'banner' => $this->getFirstMediaUrl('banner', 'preview') ?: null,
            'link' => $this->link,
        ];

        // include pivot attributes when loaded via campaigns relation
        if ($this->pivot) {
            $data['is_primary'] = (bool) $this->pivot->is_primary;
            $data['sort_order'] = $this->pivot->sort_order;
        }

        return $data;
    }
}
