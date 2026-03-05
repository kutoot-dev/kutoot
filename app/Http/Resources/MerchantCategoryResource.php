<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\MerchantCategory
 */
class MerchantCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl('image', 'preview') ?: null,
            'icon' => $this->getFirstMediaUrl('icon', 'preview') ?: null,
            'serial' => $this->serial,
        ];
    }
}
