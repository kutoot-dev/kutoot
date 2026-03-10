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
        $uploadedPath = AdminSetting::get('qr_logo');

        if ($uploadedPath && Storage::disk('public')->exists($uploadedPath)) {
            return Storage::disk('public')->path($uploadedPath);
        }

        $defaultPath = public_path('images/kutoot-logo-initial.png');

        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return null;
    }
}
