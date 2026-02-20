<?php

namespace App\Http\Controllers;

use App\Enums\QrCodeStatus;
use App\Http\Requests\GenerateBatchQrRequest;
use App\Http\Requests\LinkQrRequest;
use App\Models\MerchantLocation;
use App\Models\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
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
        $url = $qrCode->url;

        $writer = new PngWriter;

        $qr = new EndroidQrCode($url);
        $qr->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(300)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $logoPath = public_path('images/kutoot-initial-logo.svg');
        $logo = null;
        if (file_exists($logoPath)) {
            $logo = new Logo($logoPath);
            $logo->setResizeToWidth(50)
                ->setPunchoutBackground(true);
        }

        $label = new Label($qrCode->unique_code);
        $label->setTextColor(new Color(79, 70, 229));

        $result = $writer->write($qr, $logo, $label);

        return response($result->getString())
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', 'attachment; filename="'.$qrCode->unique_code.'.png"');
    }
}
