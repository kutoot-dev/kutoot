<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantCategoryResource;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantCategory;
use App\Models\MerchantLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Merchant Categories
 */
class MerchantCategoryController extends Controller
{
    /**
     * List active merchant categories
     *
     * Returns all active merchant categories ordered by serial and name.
     *
     * @queryParam search string Search by category name (partial match).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Cache::remember('merchant-categories:active:'.md5($request->input('search', '')), 300, function () use ($request) {
            return MerchantCategory::query()
                ->where('is_active', true)
                ->with('media')
                ->when($request->input('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('serial')
                ->orderBy('name')
                ->get();
        });

        return MerchantCategoryResource::collection($categories);
    }

    /**
     * List merchants (stores) by category
     *
     * Returns paginated merchant locations belonging to a category with optional filters.
     *
     * @queryParam city_id int Filter by city ID.
     * @queryParam state_id int Filter by state ID.
     * @queryParam tags string Comma-separated tag IDs.
     * @queryParam search string Search by branch name, merchant name, or address.
     * @queryParam per_page int Items per page (default: 15, max: 50).
     */
    public function stores(Request $request, MerchantCategory $merchantCategory): AnonymousResourceCollection
    {
        $merchants = MerchantLocation::query()
            ->where('merchant_category_id', $merchantCategory->id)
            ->where('is_active', true)
            ->with(['merchant', 'tags', 'media', 'state', 'city'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('branch_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('merchant', fn ($mq) => $mq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('city', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('state', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
                }
                );
            })
            ->when($request->input('state_id'), function ($q, $stateId) {
                $q->where('state_id', $stateId);
            })
            ->when($request->input('city_id'), function ($q, $cityId) {
                $q->where('city_id', $cityId);
            })
            ->when($request->input('tags'), function ($q, $tags) {
                $tagIds = array_filter(explode(',', $tags));
                if (count($tagIds) > 0) {
                    $q->whereHas('tags', fn ($tq) => $tq->whereIn('tags.id', $tagIds));
                }
            })
            ->orderBy('branch_name')
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return MerchantLocationResource::collection($merchants);
    }
}
