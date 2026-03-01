<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\SmsContract;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MerchantLocationOtpController extends Controller
{
    private const OTP_TTL_SECONDS = 300; // 5 minutes

    public function __construct(protected SmsContract $sms) {}

    /**
     * Send OTP to phone number for merchant location registration.
     */
    public function sendPhoneOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
        ]);

        $phone = $request->input('phone');
        $otp = $this->generateOtp();

        Cache::put("ml_otp_phone:{$phone}", $otp, self::OTP_TTL_SECONDS);

        Log::info("Merchant Location Phone OTP for [{$phone}]: {$otp}");

        if (app()->isProduction()) {
            $message = "Your Kutoot store registration OTP is: {$otp}. Valid for 5 minutes. Do not share this code. -Team Kutoot";
            $this->sms->send($phone, $message);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your mobile number.',
            ...(!app()->isProduction() ? ['debug_otp' => $otp] : []),
        ]);
    }

    /**
     * Verify phone OTP for merchant location registration.
     */
    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $request->input('phone');
        $otp = $request->input('otp');

        $cachedOtp = Cache::get("ml_otp_phone:{$phone}");

        if (! $cachedOtp || $cachedOtp !== $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        Cache::forget("ml_otp_phone:{$phone}");
        Cache::put("ml_verified_phone:{$phone}", true, 1800); // valid for 30 minutes

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
        ]);
    }

    /**
     * Send OTP to email for merchant location registration.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->input('email');
        $otp = $this->generateOtp();

        Cache::put("ml_otp_email:{$email}", $otp, self::OTP_TTL_SECONDS);

        Log::info("Merchant Location Email OTP for [{$email}]: {$otp}");

        if (app()->isProduction()) {
            Mail::to($email)->send(new OtpMail($otp));
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your email.',
            ...(!app()->isProduction() ? ['debug_otp' => $otp] : []),
        ]);
    }

    /**
     * Verify email OTP for merchant location registration.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = $request->input('email');
        $otp = $request->input('otp');

        $cachedOtp = Cache::get("ml_otp_email:{$email}");

        if (! $cachedOtp || $cachedOtp !== $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP. Please try again.',
            ], 422);
        }

        Cache::forget("ml_otp_email:{$email}");
        Cache::put("ml_verified_email:{$email}", true, 1800); // valid for 30 minutes

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    /**
     * Generate a random 6-digit OTP.
     */
    private function generateOtp(): string
    {
        $length = (int) config('auth.otp_length', 6);
        $max = (int) str_repeat('9', $length);

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
