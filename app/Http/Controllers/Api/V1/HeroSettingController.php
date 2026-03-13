<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HeroSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroSettingController extends Controller
{
    /**
     * Return the currently active hero setting (public endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->query('locale') ?: app()->getLocale();
        $setting = HeroSetting::active($locale);

        if (! $setting) {
            return response()->json([
                'data' => null,
            ]);
        }

        $media = $setting->getMedia('hero_media');
        $mediaArray = $media->map(function ($m) {
            $isVideo = str_starts_with($m->mime_type, 'video/');

            $item = [
                'id' => $m->id,
                'url' => $m->getUrl(),
                'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
                'mime_type' => $m->mime_type,
                'size' => $m->size,
                'width' => $m->getCustomProperty('width'),
                'height' => $m->getCustomProperty('height'),
            ];

            if (! $isVideo) {
                $item['preview'] = $m->hasGeneratedConversion('preview') ? $m->getUrl('preview') : $m->getUrl();
                $item['mobile'] = $m->hasGeneratedConversion('mobile') ? $m->getUrl('mobile') : $m->getUrl();

                $responsiveUrls = $m->responsiveImages('preview')->getUrls();
                if (! empty($responsiveUrls)) {
                    $item['srcset'] = implode(', ', array_map(
                        fn ($url) => $url,
                        $responsiveUrls
                    ));
                }
            }

            return $item;
        })->values()->toArray();

        return response()->json([
            'data' => [
                'locale' => $locale,
                'title' => $setting->title,
                'description' => $setting->description,
                'is_active' => $setting->is_active,
                'media' => $mediaArray,
            ],
        ]);
    }
}
