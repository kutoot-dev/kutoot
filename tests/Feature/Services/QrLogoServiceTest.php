<?php

use App\Models\AdminSetting;
use App\Services\QrLogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns default logo path when no admin setting exists', function () {
    $path = QrLogoService::getLogoPath();

    // Should fall back to the public default logo used by the service
    $default = public_path('images/k-logo.png');
    if (file_exists($default)) {
        expect($path)->toBe($default);
    } else {
        expect($path)->toBeNull();
    }
});

it('returns uploaded path when admin setting exists and file is present', function () {
    // Create a temp file on the real public disk
    $tempPath = 'settings/test-qr-logo.png';
    Storage::disk('public')->put($tempPath, 'fake-image-content');

    AdminSetting::updateOrCreate(
        ['key' => 'qr_logo'],
        ['value' => $tempPath, 'type' => 'string', 'group' => 'branding', 'label' => 'QR logo']
    );

    cache()->forget('admin_setting:qr_logo');

    $path = QrLogoService::getLogoPath();

    // path may include storage/app/public prefix, so just assert the uploaded
    // segment is present rather than relying on exact suffix.
    // normalize separators so we can compare reliably on Windows
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    expect($normalized)->toContain('settings' . DIRECTORY_SEPARATOR . 'test-qr-logo.png');

    // Cleanup
    Storage::disk('public')->delete($tempPath);
    AdminSetting::where('key', 'qr_logo')->delete();
    cache()->forget('admin_setting:qr_logo');
});

it('falls back to default when admin setting points to a missing file', function () {
    AdminSetting::updateOrCreate(
        ['key' => 'qr_logo'],
        ['value' => 'settings/non-existent.png', 'type' => 'string', 'group' => 'branding', 'label' => 'QR logo']
    );

    cache()->forget('admin_setting:qr_logo');

    $path = QrLogoService::getLogoPath();

    // Should NOT return the missing uploaded path
    $default = public_path('images/k-logo.png');
    if (file_exists($default)) {
        expect($path)->toBe($default);
    } else {
        expect($path)->toBeNull();
    }

    // Cleanup
    AdminSetting::where('key', 'qr_logo')->delete();
    cache()->forget('admin_setting:qr_logo');
});

it('gracefully falls back when storage throws an existence exception', function () {
    $tempPath = 'settings/broken.png';
    AdminSetting::updateOrCreate(
        ['key' => 'qr_logo'],
        ['value' => $tempPath, 'type' => 'string', 'group' => 'branding', 'label' => 'QR logo']
    );

    cache()->forget('admin_setting:qr_logo');

    // stub disk to throw an exception on exists()
    $stub = new class {
        public function exists($path)
        {
            throw new \League\Flysystem\UnableToCheckFileExistence("Unable to check existence for: {$path}");
        }
    };

    Storage::shouldReceive('disk')->with('public')->andReturn($stub);

    $path = QrLogoService::getLogoPath();

    $default = public_path('images/k-logo.png');
    if (file_exists($default)) {
        expect($path)->toBe($default);
    } else {
        expect($path)->toBeNull();
    }

    // Cleanup
    AdminSetting::where('key', 'qr_logo')->delete();
    cache()->forget('admin_setting:qr_logo');
});
