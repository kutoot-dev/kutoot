<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Merchant Locations
 */
class MerchantLocationController extends Controller
{
    /**
     * List merchant locations
     *
     * Returns a paginated list of merchant locations with optional search and filters.
     *
     * @queryParam search string Search by branch name or merchant name.
     * @queryParam merchant_id int Filter by merchant ID.
     * @queryParam is_active bool Filter by active status.
     * @queryParam per_page int Items per page (default: 15, max: 50).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $locations = MerchantLocation::query()
            ->with(['merchant', 'primaryQrCode'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('branch_name', 'like', "%{$search}%")
                        ->orWhereHas('merchant', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('merchant_id'), fn ($q, $mId) => $q->where('merchant_id', $mId))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return MerchantLocationResource::collection($locations);
    }

    /**
     * Show a single merchant location
     *
     * Returns the details of a specific merchant location by ID.
     * Used by the QR → pay bill flow to fetch store details directly.
     */
    public function show(MerchantLocation $merchantLocation): MerchantLocationResource
    {
        $merchantLocation->load(['merchant', 'state', 'city', 'tags', 'primaryQrCode']);

        return new MerchantLocationResource($merchantLocation);
    }
}
