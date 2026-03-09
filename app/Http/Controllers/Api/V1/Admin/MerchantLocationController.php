<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMerchantLocationRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMerchantLocationRequest;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin / Merchant Locations
 */
class MerchantLocationController extends Controller
{
    /**
     * List all merchant locations.
     *
     * @queryParam filter[merchant_id] int Filter by merchant.
     * @queryParam filter[is_active] boolean Filter by active status.
     * @queryParam search string Search by branch name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MerchantLocation::class);

        $locations = MerchantLocation::query()
            ->with(['merchant', 'media', 'primaryQrCode'])
            ->withCount(['transactions', 'qrCodes', 'coupons'])
            ->when($request->input('filter.merchant_id'), fn ($q, $id) => $q->where('merchant_id', $id))
            ->when($request->has('filter.is_active'), fn ($q) => $q->where('is_active', $request->boolean('filter.is_active')))
            ->when($request->input('search'), fn ($q, $s) => $q->where('branch_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return MerchantLocationResource::collection($locations);
    }

    /**
     * Show a merchant location.
     */
    public function show(MerchantLocation $merchantLocation): MerchantLocationResource
    {
        $this->authorize('view', $merchantLocation);

        $merchantLocation->load(['merchant', 'media', 'primaryQrCode']);
        $merchantLocation->loadCount(['transactions', 'qrCodes', 'coupons']);

        return new MerchantLocationResource($merchantLocation);
    }

    /**
     * Create a new merchant location.
     */
    public function store(StoreMerchantLocationRequest $request): JsonResponse
    {
        $location = MerchantLocation::create($request->validated());

        $location->load(['merchant', 'media', 'primaryQrCode']);

        return (new MerchantLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a merchant location.
     */
    public function update(UpdateMerchantLocationRequest $request, MerchantLocation $merchantLocation): MerchantLocationResource
    {
        $merchantLocation->update($request->validated());

        $merchantLocation->load(['merchant', 'media', 'primaryQrCode']);

        return new MerchantLocationResource($merchantLocation);
    }

    /**
     * Delete a merchant location.
     */
    public function destroy(MerchantLocation $merchantLocation): JsonResponse
    {
        $this->authorize('delete', $merchantLocation);

        $merchantLocation->delete();

        return response()->json(['message' => 'Merchant location deleted.'], 200);
    }
}
