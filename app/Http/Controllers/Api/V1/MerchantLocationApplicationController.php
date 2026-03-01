<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MerchantApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MerchantLocationApplicationController extends Controller
{
    /**
     * Submit a new merchant location application.
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'storeName' => ['required', 'string', 'min:2', 'max:255'],
            'ownerMobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'ownerEmail' => ['required', 'email', 'max:255'],
            'storeType' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:65535'],
            'gstNumber' => ['nullable', 'string', 'max:255'],
            'panNumber' => ['nullable', 'string', 'max:255'],
            'bankName' => ['nullable', 'string', 'max:255'],
            'subBankName' => ['nullable', 'string', 'max:255'],
            'accountNumber' => ['nullable', 'string', 'max:255'],
            'ifscCode' => ['nullable', 'string', 'max:255'],
            'upiId' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = $request->input('ownerMobile');
        $email = $request->input('ownerEmail');

        // Verify that phone and email were validated via OTP
        $phoneVerified = Cache::get("ml_verified_phone:{$phone}");
        $emailVerified = Cache::get("ml_verified_email:{$email}");

        if (! $phoneVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is not verified. Please verify via OTP first.',
            ], 422);
        }

        if (! $emailVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Email address is not verified. Please verify via OTP first.',
            ], 422);
        }

        // Check for duplicate pending application with same phone
        $existing = MerchantApplication::where('owner_mobile', $phone)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending application. Our team will contact you soon.',
            ], 422);
        }

        $application = MerchantApplication::create([
            'store_name' => $request->input('storeName'),
            'owner_mobile' => $phone,
            'owner_email' => $email,
            'store_type' => $request->input('storeType'),
            'address' => $request->input('address'),
            'gst_number' => $request->input('gstNumber'),
            'pan_number' => $request->input('panNumber'),
            'bank_name' => $request->input('bankName'),
            'sub_bank_name' => $request->input('subBankName'),
            'account_number' => $request->input('accountNumber'),
            'ifsc_code' => $request->input('ifscCode'),
            'upi_id' => $request->input('upiId'),
            'phone_verified' => true,
            'email_verified' => true,
            'status' => 'pending',
        ]);

        // Clear verification flags after successful application
        Cache::forget("ml_verified_phone:{$phone}");
        Cache::forget("ml_verified_email:{$email}");

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully. Our team will review and contact you soon.',
            'applicationId' => $application->id,
            'status' => 'PENDING',
        ], 201);
    }
}
