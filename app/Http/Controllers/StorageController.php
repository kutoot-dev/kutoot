<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Stream files from storage (S3 or local) via /storage/{path}.
     * Works with private S3 buckets - Laravel uses IAM credentials to fetch.
     */
    public function stream(string $path): StreamedResponse
    {
        $disk = Storage::disk(SettingService::getStorageDisk());

        if (!$disk->exists($path)) {
            abort(404, 'File not found');
        }

        try {
            $response = $disk->response($path);
            $response->headers->set('Content-Type', $this->getMimeType($path));
            $response->headers->set('Accept-Ranges', 'bytes');

            return $response;
        }
        catch (\Throwable $e) {
            Log::error('Storage stream failed', [
                'path' => $path,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            // S3 Access Denied = IAM missing s3:GetObject permission
            if (str_contains($e->getMessage(), '403')
            || str_contains($e->getMessage(), 'Access Denied')
            || str_contains($e->getMessage(), 'AccessDenied')) {
                abort(403, 'Access denied. Check AWS IAM has s3:GetObject on bucket.');
            }

            abort(404, 'File not found');
        }
    }

    private function getMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'mov' => 'video/quicktime',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'application/octet-stream',
            };
    }
}
