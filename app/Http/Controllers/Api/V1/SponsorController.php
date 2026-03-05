<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SponsorResource;
use App\Models\Sponsor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Sponsors
 */
class SponsorController extends Controller
{
    /**
     * List active sponsors
     *
     * Returns all active sponsors ordered by serial number.
     */
    public function index(): AnonymousResourceCollection
    {
        $sponsors = Cache::remember('sponsors:active', 300, function () {
            return Sponsor::query()
                ->where('is_active', true)
                ->with('media')
                ->orderBy('serial')
                ->get();
        });

        return SponsorResource::collection($sponsors);
    }
}
