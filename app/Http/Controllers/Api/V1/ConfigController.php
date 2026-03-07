<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Configuration
 */
class ConfigController extends Controller
{
    /**
     * Public Config
     *
     * Returns non-sensitive platform configuration for the frontend.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SettingService::getPublicConfig(),
        ]);
    }

    /**
     * Config Status
     *
     * Returns the configuration health status for all integrations.
     * Admin-only endpoint showing which required settings are missing.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => SettingService::getConfigStatus(),
        ]);
    }
}
