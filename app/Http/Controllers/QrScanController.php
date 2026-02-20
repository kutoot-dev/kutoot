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

        return redirect()->route('coupons.index', ['location' => $qrCode->merchant_location_id])
            ->with('message', 'Welcome to '.$qrCode->merchantLocation->branch_name);
    }
}
