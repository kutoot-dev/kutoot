<?php

use App\Models\AdminSetting;
use App\Services\QrLogoService;
use Illuminate\Support\Facades\Storage;

it('returns default logo path when no admin setting exists', function () {
    $path = QrLogoService::getLogoPath();

    // Should fall back to the public default logo
    if (file_exists(public_path('images/kutoot-logo-initial.png'))) {
        expect($path)->toBe(public_path('images/kutoot-logo-initial.png'));
    }
    else {
        expect($path)->toBeNull();
    }
});

it('returns uploaded path when admin setting exists and file is present', function () {
    // Create a temp file on the real public disk
    $tempPath = 'settings/test-qr-logo.png';
    Storage::disk('public')->put($tempPath, 'fake-image-content');

    AdminSetting::updateOrCreate(
    ['key' => 'qr_logo'],
    ['value' => $tempPath, 'type' => 'string', 'group' => 'branding']
    );

    cache()->forget('admin_setting:qr_logo');

    $path = QrLogoService::getLogoPath();

    expect($path)->toEndWith('settings' . DIRECTORY_SEPARATOR . 'test-qr-logo.png');

    // Cleanup
    Storage::disk('public')->delete($tempPath);
    AdminSetting::where('key', 'qr_logo')->delete();
    cache()->forget('admin_setting:qr_logo');
});

it('falls back to default when admin setting points to a missing file', function () {
    AdminSetting::updateOrCreate(
    ['key' => 'qr_logo'],
    ['value' => 'settings/non-existent.png', 'type' => 'string', 'group' => 'branding']
    );

    cache()->forget('admin_setting:qr_logo');

    $path = QrLogoService::getLogoPath();

    // Should NOT return the missing uploaded path
    if (file_exists(public_path('images/kutoot-logo-initial.png'))) {
        expect($path)->toBe(public_path('images/kutoot-logo-initial.png'));
    }
    else {
        expect($path)->toBeNull();
    }

    // Cleanup
    AdminSetting::where('key', 'qr_logo')->delete();
    cache()->forget('admin_setting:qr_logo');
});
