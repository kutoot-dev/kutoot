<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MerchantLocationAuthController extends Controller
{
    /**
     * Login for merchant location users (username + password).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device' => ['nullable', 'array'],
            'device.platform' => ['nullable', 'string'],
            'device.browser' => ['nullable', 'string'],
            'device.ip' => ['nullable', 'string'],
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password.',
            ], 401);
        }

        // Check that this user is associated with a merchant location
        $location = $user->merchantLocations()->with(['merchant', 'merchantCategory', 'qrCodes'])->first();

        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'No store associated with this account. Please contact support.',
            ], 403);
        }

        // Revoke old tokens and create a new one
        $user->tokens()->delete();

        $token = $user->createToken('merchant-location', ['merchant:*'])->plainTextToken;

        $pivotRole = $location->pivot->role ?? 'owner';

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'seller' => [
                    'sellerId' => $user->id,
                    'shopId' => $location->id,
                    'shopName' => $location->branch_name,
                    'ownerName' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->mobile,
                    'status' => $location->is_active ? 'active' : 'inactive',
                    'role' => $pivotRole,
                    'category' => $location->merchantCategory?->name,
                    'merchantName' => $location->merchant?->name,
                    'qrCodes' => $location->qrCodes->map(fn ($qr) => [
                        'unique_code' => $qr->unique_code,
                        'token' => $qr->token,
                        'status' => $qr->status,
                        'is_primary' => (bool)$qr->is_primary,
                        'url' => $qr->url,
                        'short_url' => $qr->short_url,
                    ])->toArray(),
                ],
            ],
        ]);
    }

    /**
     * Get the authenticated merchant user's profile and locations.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $locations = $user->merchantLocations()
            ->with(['merchant', 'merchantCategory', 'qrCodes'])
            ->get();

        $primaryLocation = $locations->first();

        if (! $primaryLocation) {
            return response()->json([
                'success' => false,
                'message' => 'No store associated with this account.',
            ], 404);
        }

        $pivotRole = $primaryLocation->pivot->role ?? 'owner';

        return response()->json([
            'success' => true,
            'data' => [
                'sellerId' => $user->id,
                'shopId' => $primaryLocation->id,
                'shopName' => $primaryLocation->branch_name,
                'ownerName' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobile,
                'status' => $primaryLocation->is_active ? 'active' : 'inactive',
                'role' => $pivotRole,
                'category' => $primaryLocation->merchantCategory?->name,
                'merchantName' => $primaryLocation->merchant?->name,
                'qrCodes' => $primaryLocation->qrCodes->map(fn ($qr) => [
                    'unique_code' => $qr->unique_code,
                    'token' => $qr->token,
                    'status' => $qr->status,
                    'is_primary' => (bool)$qr->is_primary,
                    'url' => $qr->url,
                    'short_url' => $qr->short_url,
                ])->toArray(),
                'locations' => $locations->map(fn ($loc) => [
                    'id' => $loc->id,
                    'branch_name' => $loc->branch_name,
                    'role' => $loc->pivot->role ?? 'staff',
                ]),
            ],
        ]);
    }

    /**
     * Logout the merchant user (revoke tokens).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
