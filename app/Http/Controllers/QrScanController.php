<?php

namespace App\Http\Controllers;

use App\Enums\QrCodeStatus;
use App\Models\QrCode;

class QrScanController extends Controller
{
    public function scan(string $token)
    {
        $qrCode = QrCode::where('token', $token)
            ->where('status', QrCodeStatus::Linked)
            ->with('merchantLocation')
            ->firstOrFail();

        // Log the scan for analytics
        activity()
            ->performedOn($qrCode)
            ->event('scanned')
            ->withProperties([
                'merchant_location_id' => $qrCode->merchant_location_id,
                'branch_name' => $qrCode->merchantLocation->branch_name,
            ])
            ->log("QR code {$qrCode->unique_code} was scanned");

        return redirect()->away(config('app.frontend_url', 'http://localhost:3000') . '/kutoot-store?store_id=' . $qrCode->merchant_location_id . '&action=paybill');
    }
}
