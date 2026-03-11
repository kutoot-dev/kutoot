<?php

namespace App\Http\Controllers;

use App\Enums\QrCodeStatus;
use App\Http\Requests\GenerateBatchQrRequest;
use App\Http\Requests\LinkQrRequest;
use App\Models\MerchantLocation;
use App\Models\QrCode;
use App\Services\QrCodeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExecutiveQrController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Executive/LinkQr', [
            'locations' => MerchantLocation::with('merchant')->get()->map(fn ($loc) => [
                'id' => $loc->id,
                'name' => $loc->branch_name.' ('.$loc->merchant->name.')',
            ]),
        ]);
    }

    public function link(LinkQrRequest $request)
    {
        $validated = $request->validated();

        $qrCode = QrCode::where('unique_code', $validated['unique_code'])->firstOrFail();

        if ($qrCode->status === QrCodeStatus::Linked) {
            return back()->with('error', 'This QR code is already linked to another location.');
        }

        $qrCode->update([
            'merchant_location_id' => $validated['merchant_location_id'],
            'status' => QrCodeStatus::Linked,
            'linked_at' => now(),
            'linked_by' => $request->user()->id,
        ]);

        return back()->with('success', 'QR Code successfully linked to '.$qrCode->merchantLocation->branch_name);
    }

    public function generateBatch(GenerateBatchQrRequest $request)
    {
        $validated = $request->validated();
        $count = $validated['count'];
        $prefix = $validated['prefix'] ?? 'KUT-';

        $latest = QrCode::where('unique_code', 'LIKE', $prefix.'%')
            ->orderBy('unique_code', 'desc')
            ->first();

        $start = $latest ? (int) str_replace($prefix, '', $latest->unique_code) + 1 : 1;

        for ($i = 0; $i < $count; $i++) {
            $num = str_pad($start + $i, 4, '0', STR_PAD_LEFT);
            QrCode::create([
                'unique_code' => $prefix.$num,
                'token' => Str::random(32),
                'status' => QrCodeStatus::Available,
            ]);
        }

        return back()->with('success', "$count QR codes generated.");
    }

    public function download(QrCode $qrCode)
    {
        $result = QrCodeBuilder::buildResult($qrCode->url, 300);

        return response($result->getString())
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', 'attachment; filename="'.$qrCode->unique_code.'.png"');
    }
}
