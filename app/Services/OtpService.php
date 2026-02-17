<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public const OTP_EXPIRY_MINUTES = 5;

    public function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        return $otp;
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        if (! $user->otp_code || ! $user->otp_expires_at) {
            return false;
        }

        if ($user->otp_expires_at->isPast()) {
            $this->clearOtp($user);

            return false;
        }

        if ($user->otp_code !== $otp) {
            return false;
        }

        $this->clearOtp($user);

        return true;
    }

    public function clearOtp(User $user): void
    {
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);
    }

    /**
     * Send OTP via the appropriate channel.
     * Currently logs OTP for development. Replace with SMS/email integration for production.
     */
    public function sendOtp(User $user, string $otp, string $channel = 'email'): void
    {
        $identifier = $user->email ?? $user->mobile;
        Log::info("OTP for {$channel} [{$identifier}]: {$otp}");
    }
}
