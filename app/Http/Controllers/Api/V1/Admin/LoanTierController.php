<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreLoanTierRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLoanTierRequest;
use App\Http\Resources\LoanTierResource;
use App\Models\LoanTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin / Loan Tiers
 */
class LoanTierController extends Controller
{
    /**
     * List all loan tiers.
     *
     * @queryParam filter[is_active] boolean Filter by active status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403);

        $tiers = LoanTier::query()
            ->when($request->has('filter.is_active'), fn ($q) => $q->where('is_active', $request->boolean('filter.is_active')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return LoanTierResource::collection($tiers);
    }

    /**
     * Show a loan tier.
     */
    public function show(LoanTier $loanTier): LoanTierResource
    {
        abort_unless(request()->user()->hasRole('Super Admin'), 403);

        return new LoanTierResource($loanTier);
    }

    /**
     * Create a new loan tier.
     */
    public function store(StoreLoanTierRequest $request): JsonResponse
    {
        $tier = LoanTier::create($request->validated());

        return (new LoanTierResource($tier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a loan tier.
     */
    public function update(UpdateLoanTierRequest $request, LoanTier $loanTier): LoanTierResource
    {
        $loanTier->update($request->validated());

        return new LoanTierResource($loanTier);
    }

    /**
     * Delete a loan tier.
     */
    public function destroy(LoanTier $loanTier): JsonResponse
    {
        abort_unless(request()->user()->hasRole('Super Admin'), 403);

        $loanTier->delete();

        return response()->json(['message' => 'Loan tier deleted.'], 200);
    }
}
