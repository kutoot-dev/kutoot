<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OtpService
{
    public function __construct(protected \App\Contracts\SmsContract $sms) {}

    public const OTP_EXPIRY_MINUTES = 5;

    public function generateOtp(User $user): string
    {
        $length = (int) config('auth.otp_length', 6);
        $max = (int) str_repeat('9', $length);
        $otp = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

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
        $length = (int) config('auth.otp_length', 6);
        $max = (int) str_repeat('9', $length);
        $otp = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

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
     */
    public function sendOtp(?User $user, string $otp, string $channel = 'email', ?string $identifier = null): void
    {
        $target = $identifier;

        if (! $target && $user) {
            $target = match ($channel) {
                'mobile' => $user->mobile,
                'email' => $user->email,
                default => $user->email ?? $user->mobile,
            };
        }

        Log::info("OTP for {$channel} [{$target}]: {$otp}");

        if ($channel === 'mobile' || ($channel === 'email' && is_numeric($target))) {
            // Ensure target is a valid mobile number format if needed, but assuming calling code validates/sanitizes
            // $message = "Your Kutoot login OTP is: {$otp} This code is valid for 10 minutes. Use it to securely access your Kutoot account. Do not share this code with anyone. -Team Kutoot | Shopping is Winning";
            // $message = "Your Kutoot login OTP is: $otp This code is valid for 10 minutes. "
            //     . "Use it to securely access your Kutoot account. Do not share this code with anyone. "
            //     . "-Team Kutoot | Shopping is Winning";
            $message = "Your Kutoot login OTP is: $otp This code is valid for 10 minutes. Use it to securely access your Kutoot account. Do not share this code with anyone. -Team Kutoot | Shopping is Winning";
            if (app()->isProduction()) {
                $this->sms->send($target, $message);
            }
        }
    }
}
