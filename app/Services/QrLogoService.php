<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Storage;

class QrLogoService
{
    /**
     * Get the absolute path to the QR code logo image.
     *
     * Priority: admin-uploaded file → default public asset → null.
     */
    public static function getLogoPath(): ?string
    {
        $defaultPath = public_path('images/k-logo.png');

        $uploadedPath = AdminSetting::get('qr_logo');
        $disk = SettingService::get('media_disk', 'public');

        if ($uploadedPath && Storage::disk($disk)->exists($uploadedPath)) {
            $adapter = Storage::disk($disk)->getAdapter();

            // If it's local, we can just use the path directly
            if ($adapter instanceof \League\Flysystem\Local\LocalFilesystemAdapter) {
                return Storage::disk($disk)->path($uploadedPath);
            }

            // For S3/cloud, cache locally to avoid downloading on every QR render
            $localCachePath = storage_path('app/public/qr_logo_cache_' . md5($uploadedPath) . '.png');
            $cloudLastModified = Storage::disk($disk)->lastModified($uploadedPath);

            if (!file_exists($localCachePath) || filemtime($localCachePath) < $cloudLastModified) {
                file_put_contents($localCachePath, Storage::disk($disk)->get($uploadedPath));
            }

            return $localCachePath;
        }


        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return null;
    }
}
