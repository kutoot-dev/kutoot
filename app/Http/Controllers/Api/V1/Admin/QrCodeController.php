<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreQrCodeRequest;
use App\Http\Requests\Api\V1\Admin\UpdateQrCodeRequest;
use App\Http\Requests\GenerateBatchQrRequest;
use App\Http\Requests\LinkQrRequest;
use App\Http\Resources\QrCodeResource;
use App\Models\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * @tags Admin / QR Codes
 */
class QrCodeController extends Controller
{
    /**
     * List all QR codes.
     *
     * @queryParam filter[status] string Filter by status (Available, Linked).
     * @queryParam filter[merchant_location_id] int Filter by merchant location.
     * @queryParam search string Search by unique code or token.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', QrCode::class);

        $qrCodes = QrCode::query()
            ->with(['merchantLocation.merchant', 'executive'])
            ->when($request->input('filter.status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('filter.merchant_location_id'), fn ($q, $id) => $q->where('merchant_location_id', $id))
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('unique_code', 'like', "%{$s}%")
                    ->orWhere('token', 'like', "%{$s}%");
            }
            ))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return QrCodeResource::collection($qrCodes);
    }

    /**
     * Show a QR code.
     */
    public function show(QrCode $qrCode): QrCodeResource
    {
        $this->authorize('view', $qrCode);

        $qrCode->load(['merchantLocation.merchant', 'executive']);

        return new QrCodeResource($qrCode);
    }

    /**
     * Create a new QR code.
     */
    public function store(StoreQrCodeRequest $request): JsonResponse
    {
        $qrCode = QrCode::create($request->validated());

        $qrCode->load(['merchantLocation.merchant', 'executive']);

        return (new QrCodeResource($qrCode))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a QR code.
     */
    public function update(UpdateQrCodeRequest $request, QrCode $qrCode): QrCodeResource
    {
        $qrCode->update($request->validated());

        $qrCode->load(['merchantLocation.merchant', 'executive']);

        return new QrCodeResource($qrCode);
    }

    /**
     * Delete a QR code.
     */
    public function destroy(QrCode $qrCode): JsonResponse
    {
        $this->authorize('delete', $qrCode);

        $qrCode->delete();

        return response()->json(['message' => 'QR code deleted.'], 200);
    }

    /**
     * Generate a batch of QR codes.
     */
    public function generateBatch(GenerateBatchQrRequest $request): JsonResponse
    {
        $this->authorize('create', QrCode::class);

        $count = $request->validated('count');
        $prefix = $request->validated('prefix', '');

        $qrCodes = collect();

        for ($i = 0; $i < $count; $i++) {
            $uniqueCode = $prefix.strtoupper(Str::random(10));
            $token = Str::uuid()->toString();

            $qrCodes->push(QrCode::create([
                'unique_code' => $uniqueCode,
                'token' => $token,
                'status' => \App\Enums\QrCodeStatus::Available,
            ]));
        }

        return response()->json([
            'message' => "{$count} QR codes generated successfully.",
            'data' => QrCodeResource::collection($qrCodes),
        ], 201);
    }

    /**
     * Link a QR code to a merchant location.
     */
    public function link(LinkQrRequest $request, QrCode $qrCode): QrCodeResource
    {
        $this->authorize('update', $qrCode);

        $qrCode->update([
            'merchant_location_id' => $request->validated('merchant_location_id'),
            'status' => \App\Enums\QrCodeStatus::Linked,
            'linked_at' => now(),
            'linked_by' => $request->user()->id,
        ]);

        $qrCode->load(['merchantLocation.merchant', 'executive']);

        return new QrCodeResource($qrCode);
    }

    /**
     * Get QR code image.
     */
    public function image(QrCode $qrCode)
    {
        $url = route('qr.scan', ['token' => $qrCode->token]);
        $logoPath = \App\Services\QrLogoService::getLogoPath();

        $builder = \Endroid\QrCode\Builder\Builder::create()
            ->data($url)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(8)
            ->roundBlockSizeMode(\Endroid\QrCode\RoundBlockSizeMode::Margin)
            ->foregroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
            ->backgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));

        if ($logoPath) {
            $builder = $builder
                ->logoPath($logoPath)
                ->logoResizeToWidth(60)
                ->logoResizeToHeight(60);
            // the logo image should include any desired background;
            // the builder no longer exposes a logoBackgroundColor option
        }

        $qrCode = $builder->build();

        return response($qrCode->getString())
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
