<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Merchant
 */
class MerchantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'razorpay_account_id' => $this->when(
                $request->user()?->can('view-any-merchant'),
                $this->razorpay_account_id
            ),
            'logo_url' => $this->getFirstMediaUrl('logo', 'thumb'),
            'media' => $this->whenLoaded('media', fn () => $this->getMedia('media')->map(function ($m) {
                $isVideo = str_starts_with($m->mime_type, 'video/');

                $item = [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
                    'name' => $m->name,
                    'mime_type' => $m->mime_type,
                    'size' => $m->size,
                    'width' => $m->getCustomProperty('width'),
                    'height' => $m->getCustomProperty('height'),
                ];

                if (! $isVideo) {
                    $item['preview'] = $m->hasGeneratedConversion('preview') ? $m->getUrl('preview') : $m->getUrl();

                    $responsiveUrls = $m->responsiveImages('preview')->getUrls();
                    if (! empty($responsiveUrls)) {
                        $item['srcset'] = implode(', ', $responsiveUrls);
                    }
                }

                return $item;
            })),
            'locations_count' => $this->whenCounted('locations'),
            'locations' => MerchantLocationResource::collection($this->whenLoaded('locations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
