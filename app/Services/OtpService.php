<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

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

    /**
     * Generate OTP stored in session (for registration, where user doesn't exist yet).
     */
    public function generateOtpForSession(string $identifier): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Session::put("otp.{$identifier}", [
            'code' => $otp,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->timestamp,
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

    /**
     * Verify OTP stored in session (for registration).
     */
    public function verifyOtpFromSession(string $identifier, string $otp): bool
    {
        $data = Session::get("otp.{$identifier}");

        if (! $data) {
            return false;
        }

        if (now()->timestamp > $data['expires_at']) {
            Session::forget("otp.{$identifier}");

            return false;
        }

        if ($data['code'] !== $otp) {
            return false;
        }

        Session::forget("otp.{$identifier}");

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
    public function sendOtp(?User $user, string $otp, string $channel = 'email', ?string $identifier = null): void
    {
        $target = $identifier ?? $user->email ?? $user->mobile;
        Log::info("OTP for {$channel} [{$target}]: {$otp}");
    }
}
