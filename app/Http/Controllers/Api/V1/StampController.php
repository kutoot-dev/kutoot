<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStampCodeRequest;
use App\Http\Resources\StampResource;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Stamps
 */
class StampController extends Controller
{
    public function __construct(protected StampService $stampService) {}

    /**
     * List stamps
     *
     * Returns the authenticated user's stamps, optionally filtered by campaign.
     * Stamps are returned in descending order (newest first).
     *
     * @queryParam campaign_id int Filter by campaign ID.
     * @queryParam per_page int Items per page (default: 15, max: 50).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $stamps = $user->stamps()
            ->when($request->input('campaign_id'), fn ($q, $cId) => $q->where('campaign_id', $cId))
            ->with(['campaign', 'transaction:id,amount,original_bill_amount'])
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return StampResource::collection($stamps);
    }

    /**
     * Update stamp code
     *
     * Updates the slot values for a stamp's code. The stamp must belong to the
     * authenticated user and be within its editable window.
     *
     * @response 200 { "data": { "id": 1, "code": "STP-01-02-03" } }
     * @response 422 { "error": "Stamp edit window has expired." }
     */
    public function updateCode(UpdateStampCodeRequest $request, \App\Models\Stamp $stamp): JsonResponse
    {
        try {
            $updatedStamp = $this->stampService->updateStampCode(
                $stamp,
                $request->validated('slot_values'),
            );

            return response()->json([
                'data' => [
                    'id' => $updatedStamp->id,
                    'code' => $updatedStamp->code,
                    'editable_until' => $updatedStamp->editable_until?->toISOString(),
                    'is_editable' => $updatedStamp->isEditable(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
