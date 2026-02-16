<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantLocationController extends Controller
{
    public function index(Request $request)
    {
        $locations = MerchantLocation::query()
            ->with('merchant')
            ->when($request->search, fn ($q, $search) => $q->where('branch_name', 'like', "%{$search}%")
                ->orWhereHas('merchant', fn ($mq) => $mq->where('name', 'like', "%{$search}%"))
            )
            ->paginate();

        return JsonResource::collection($locations);
    }
}
