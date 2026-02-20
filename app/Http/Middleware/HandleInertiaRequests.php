<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'currency' => config('app.currency'),
            'platform_fee' => config('app.platform_fee'),
            'gst_rate' => config('app.gst_rate'),
            'platform_fee_type' => config('app.platform_fee_type'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'needsCampaignSelection' => fn () => $request->session()->get('needsCampaignSelection'),
            ],
            'otpLength' => (int) config('auth.otp_length', 6),
        ];
    }
}
