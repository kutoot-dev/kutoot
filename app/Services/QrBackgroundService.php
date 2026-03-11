<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Storage;

class QrBackgroundService
{
    /**
     * Default background path relative to public.
     */
    protected static string $defaultPath = 'images/qr-background.png';

    /**
     * Get the URL for the QR print background image.
     *
     * Priority: admin-uploaded file → default public asset.
     * Returns a full URL suitable for use in CSS background-image.
     */
    public static function getBackgroundUrl(): string
    {
        $uploadedPath = AdminSetting::get('qr_background');

        if ($uploadedPath) {
            $disk = SettingService::getStorageDisk();
            try {
                $diskInstance = Storage::disk($disk);

                if ($diskInstance->exists($uploadedPath)) {
                    return $diskInstance->url($uploadedPath);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('QR background storage check failed', [
                    'disk' => $disk,
                    'path' => $uploadedPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        

        return asset(static::$defaultPath);
    }
}
