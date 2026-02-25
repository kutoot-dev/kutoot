<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @tags Profile
 */
class ProfileController extends Controller
{
    /**
     * Get profile
     *
     * Returns the authenticated user's profile with subscription and primary campaign.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load(['primaryCampaign', 'activeSubscription.plan', 'roles'])
        );
    }

    /**
     * Update profile
     *
     * Updates the authenticated user's name and/or email.
     *
     * @response 200 { "data": { "id": 1, "name": "Updated Name" } }
     */
    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        // save picture if provided
        if ($request->hasFile('profile_picture')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('profile_picture')->toMediaCollection('avatar');
        }

        $user->fill(Arr::except($data, ['profile_picture']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->load(['primaryCampaign', 'activeSubscription.plan']));
    }

    /**
     * Update primary campaign
     *
     * Updates the user's primary campaign selection.
     *
     * @response 200 { "message": "Primary campaign updated successfully." }
     * @response 422 { "message": "The selected campaign is invalid." }
     */
    public function updatePrimaryCampaign(Request $request): JsonResponse
    {
        $request->validate([
            'primary_campaign_id' => ['required', 'exists:campaigns,id'],
        ]);

        // Verify the campaign is accessible under user's plan
        $accessibleIds = $request->user()->accessibleCampaignIds();
        if (! $accessibleIds->contains($request->primary_campaign_id)) {
            return response()->json([
                'error' => 'This campaign is not available under your current plan.',
            ], 422);
        }

        $request->user()->update([
            'primary_campaign_id' => $request->primary_campaign_id,
        ]);

        return response()->json([
            'message' => 'Primary campaign updated successfully.',
        ]);
    }

    /**
     * Delete account
     *
     * Permanently deletes the authenticated user's account and revokes all tokens.
     *
     * @response 204
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        $user->delete();

        return response()->json(null, 204);
    }
}
