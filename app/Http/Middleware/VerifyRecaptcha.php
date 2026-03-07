<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    /**
     * Handle an incoming request.
     *
     * Validates Google reCAPTCHA v3 token if reCAPTCHA is enabled in admin settings.
     * When disabled, the middleware is a no-op and passes through.
     */
    public function handle(Request $request, Closure $next, float $threshold = 0.5): Response
    {
        $enabled = (bool) SettingService::get('recaptcha_enabled', false);

        if (! $enabled) {
            return $next($request);
        }

        $token = $request->input('recaptcha_token') ?? $request->header('X-Recaptcha-Token');

        if (! $token) {
            return response()->json([
                'message' => 'reCAPTCHA verification is required.',
                'errors' => ['recaptcha_token' => ['reCAPTCHA token is missing.']],
            ], 422);
        }

        $secretKey = SettingService::get('recaptcha_secret_key', '');

        if (blank($secretKey)) {
            // If secret key is not configured, skip verification
            return $next($request);
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            $result = $response->json();

            if (! ($result['success'] ?? false) || ($result['score'] ?? 0) < $threshold) {
                return response()->json([
                    'message' => 'reCAPTCHA verification failed. Please try again.',
                    'errors' => ['recaptcha_token' => ['Automated request detected. Please try again.']],
                ], 422);
            }
        } catch (\Exception $e) {
            // On network failure, allow through (don't block legitimate users)
            report($e);
        }

        return $next($request);
    }
}
