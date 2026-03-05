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

        return response()->json([
            'data' => [
                'locale' => $locale,
                'title' => $setting->title,
                'description' => $setting->description,
                'is_active' => $setting->is_active,
            ],
        ]);
    }
}
