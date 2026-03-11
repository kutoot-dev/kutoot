<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $disk = SettingService::getStorageDisk();

        if ($uploadedPath) {
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $diskInstance */
                $diskInstance = Storage::disk($disk);

                if ($diskInstance->exists($uploadedPath)) {
                    $adapter = $diskInstance->getAdapter();

                    // If it's local, we can just use the path directly
                    if ($adapter instanceof \League\Flysystem\Local\LocalFilesystemAdapter) {
                        return $diskInstance->path($uploadedPath);
                    }

                    // For S3/cloud, cache locally to avoid downloading on every QR render
                    $localCachePath = storage_path('app/public/qr_logo_cache_' . md5($uploadedPath) . '.png');
                    $cloudLastModified = $diskInstance->lastModified($uploadedPath);

                    if (!file_exists($localCachePath) || filemtime($localCachePath) < $cloudLastModified) {
                        file_put_contents($localCachePath, $diskInstance->get($uploadedPath));
                    }

                    return $localCachePath;
                }
            } catch (\Throwable $e) {
                // Storage operations can fail (bad credentials, network issues, etc.).
                // Log and continue to fall back to default path instead of bubbling up.
                \Illuminate\Support\Facades\Log::warning('QR logo storage check failed', [
                    'disk' => $disk,
                    'path' => $uploadedPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }


        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return null;
    }
}
