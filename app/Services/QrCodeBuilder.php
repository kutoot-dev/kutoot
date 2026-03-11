<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\Result\ResultInterface;

class QrCodeBuilder
{
    /**
     * Build a QR code for the given URL and return its data URI.
     *
     * Uses k-logo.png (or admin-uploaded logo) at center, with 10% size reduction.
     *
     * @param  int  $baseSize  Base size in pixels; will be reduced by 10% (e.g. 400 → 360)
     */
    public static function buildForUrl(string $url, int $baseSize = 400): string
    {
        return static::buildResult($url, $baseSize)->getDataUri();
    }

    /**
     * Build a QR code for the given URL and return the writer result (for download, etc.).
     *
     * @param  int  $baseSize  Base size in pixels; will be reduced by 10%
     */
    public static function buildResult(string $url, int $baseSize = 400): ResultInterface
    {
        $size = (int) round($baseSize * 0.9); // 10% reduction

        $builder = Builder::create()
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin);

        $logoPath = QrLogoService::getLogoPath();
        if ($logoPath) {
            $logoWidth = (int) round($size * 0.2); // Logo ~20% of QR size
            $builder
                ->logoPath($logoPath)
                ->logoResizeToWidth($logoWidth)
                ->logoPunchoutBackground(true);
        }

        return $builder->build();
    }
}
