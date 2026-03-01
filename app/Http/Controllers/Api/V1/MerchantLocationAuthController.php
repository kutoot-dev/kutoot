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
        $location = $user->merchantLocations()->with('merchant', 'merchantCategory')->first();

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
                ],
            ],
        ]);
    }
}
