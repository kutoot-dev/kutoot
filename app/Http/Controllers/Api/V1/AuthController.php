<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpSendRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Resources\UserResource;
use App\Models\AdminSetting;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SettingService;
use App\Services\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @tags Authentication
 */
class AuthController extends Controller
{
    public function __construct(
        public OtpService $otpService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Send OTP
     *
     * Send a one-time password to the user's email or mobile number.
     * If the user doesn't exist, a new account is created automatically.
     *
     * @unauthenticated
     */
    public function sendOtp(OtpSendRequest $request): JsonResponse
    {
        $identifier = $request->validated('identifier');
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = User::query()
            ->when($isEmail, fn ($q) => $q->where('email', $identifier))
            ->when(! $isEmail, fn ($q) => $q->where('mobile', $identifier))
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $isEmail ? strstr($identifier, '@', true) : 'User '.$identifier,
                'email' => $isEmail ? $identifier : null,
                'mobile' => $isEmail ? null : $identifier,
            ]);

            event(new Registered($user));
        }

        $otp = $this->otpService->generateOtp($user);
        $channel = $isEmail ? 'email' : 'mobile';
        $this->otpService->sendOtp($user, $otp, $channel);

        $response = [
            'message' => 'OTP sent successfully! Check your '.($isEmail ? 'email' : 'phone').'.',
            'channel' => $channel,
        ];

        if (! app()->isProduction()) {
            $response['debug_otp'] = $otp;
        }

        return response()->json($response);
    }

    /**
     * Verify OTP
     *
     * Verify the OTP and receive a Sanctum bearer token for subsequent API requests.
     *
     * @unauthenticated
     *
     * @response 200 { "token": "1|abc123...", "user": { "id": 1, "name": "John", "email": "john@example.com" } }
     * @response 422 { "message": "Invalid or expired OTP.", "errors": { "otp": ["Invalid or expired OTP. Please try again."] } }
     */
    public function verifyOtp(OtpVerifyRequest $request): JsonResponse
    {
        $identifier = $request->validated('identifier');
        $otp = $request->validated('otp');
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = User::query()
            ->when($isEmail, fn ($q) => $q->where('email', $identifier))
            ->when(! $isEmail, fn ($q) => $q->where('mobile', $identifier))
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $isEmail ? strstr($identifier, '@', true) : 'User '.$identifier,
                'email' => $isEmail ? $identifier : null,
                'mobile' => $isEmail ? null : $identifier,
            ]);

            event(new Registered($user));
        }

        if (! $this->otpService->verifyOtp($user, $otp)) {
            throw ValidationException::withMessages([
                'otp' => __('Invalid or expired OTP. Please try again.'),
            ]);
        }

        // Determine token abilities based on user roles
        $abilities = ['user:*'];
        if ($user->hasRole('Super Admin')) {
            $abilities = ['*'];
        } elseif ($user->hasAnyRole(['Merchant Admin', 'Executive'])) {
            $abilities = ['user:*', 'merchant:*'];
        }

        $deviceName = $request->input('device_name', 'api-token');
        $token = $user->createToken($deviceName, $abilities);

        // Assign default plan on first login (if no active subscription exists)
        Log::info('attempting assignDefaultPlan during login', ['user_id' => $user->id]);
        try {
            $this->subscriptionService->assignDefaultPlan($user);
        } catch (\Throwable $e) {
            Log::error('assignDefaultPlan failed during login', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            report($e); // Log but don't block login
        }
        $user->refresh();

        // Check if user needs to accept platform terms
        $requiresTerms = false;
        $activeTerms = \App\Models\PlatformTerms::active();

        if ($activeTerms && (
            ! $user->terms_accepted_at ||
            ! $user->terms_version_id ||
            $user->terms_version_id < $activeTerms->id
        )) {
            $requiresTerms = true;
        }

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->load(['primaryCampaign', 'activeSubscription.plan'])),
            'requires_terms_acceptance' => $requiresTerms,
        ]);
    }

    /**
     * Get authenticated user
     *
     * Returns the currently authenticated user with their subscription and primary campaign.
     *
     * @response 200 { "data": { "id": 1, "name": "John", "email": "john@example.com" } }
     */
    public function user(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load(['primaryCampaign', 'activeSubscription.plan'])
        );
    }

    /**
     * Logout
     *
     * Revoke the current API token.
     *
     * @response 204
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Auth Config
     *
     * Returns public authentication configuration (login methods, reCAPTCHA settings).
     *
     * @unauthenticated
     */
    public function config(): JsonResponse
    {
        $loginMethods = SettingService::get('auth_login_methods', 'otp');

        return response()->json([
            'login_methods' => $loginMethods,
            'recaptcha_enabled' => (bool) SettingService::get('recaptcha_enabled', false),
            'recaptcha_site_key' => SettingService::get('recaptcha_site_key', ''),
        ]);
    }

    /**
     * Register
     *
     * Register a new user with email/mobile and password.
     *
     * @unauthenticated
     */
    public function register(Request $request): JsonResponse
    {
        $loginMethods = SettingService::get('auth_login_methods', 'otp');

        if (! in_array($loginMethods, ['password', 'both'])) {
            return response()->json(['message' => 'Password registration is not enabled.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'nullable|string|min:10|max:15|unique:users,mobile',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $request->email && ! $request->mobile) {
            throw ValidationException::withMessages([
                'identifier' => 'Either email or mobile number is required.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Assign default plan
        try {
            $this->subscriptionService->assignDefaultPlan($user);
        } catch (\Throwable $e) {
            report($e);
        }
        $user->refresh();

        $token = $user->createToken($request->input('device_name', 'api-token'), ['user:*']);

        // Check if user needs to accept platform terms
        $requiresTerms = false;
        $activeTerms = \App\Models\PlatformTerms::active();

        if ($activeTerms) {
            $requiresTerms = true;
        }

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->load(['primaryCampaign', 'activeSubscription.plan'])),
            'requires_terms_acceptance' => $requiresTerms,
        ], 201);
    }

    /**
     * Password Login
     *
     * Login with email/mobile and password.
     *
     * @unauthenticated
     */
    public function login(Request $request): JsonResponse
    {
        $loginMethods = SettingService::get('auth_login_methods', 'otp');

        if (! in_array($loginMethods, ['password', 'both'])) {
            return response()->json(['message' => 'Password login is not enabled.'], 403);
        }

        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = $request->identifier;
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $user = User::query()
            ->when($isEmail, fn ($q) => $q->where('email', $identifier))
            ->when(! $isEmail, fn ($q) => $q->where('mobile', $identifier))
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => __('Invalid credentials.'),
            ]);
        }

        // Determine token abilities based on user roles
        $abilities = ['user:*'];
        if ($user->hasRole('Super Admin')) {
            $abilities = ['*'];
        } elseif ($user->hasAnyRole(['Merchant Admin', 'Executive'])) {
            $abilities = ['user:*', 'merchant:*'];
        }

        $token = $user->createToken($request->input('device_name', 'api-token'), $abilities);

        // Assign default plan if needed
        try {
            $this->subscriptionService->assignDefaultPlan($user);
        } catch (\Throwable $e) {
            report($e);
        }
        $user->refresh();

        // Check if user needs to accept platform terms
        $requiresTerms = false;
        $activeTerms = \App\Models\PlatformTerms::active();

        if ($activeTerms && (
            ! $user->terms_accepted_at ||
            ! $user->terms_version_id ||
            $user->terms_version_id < $activeTerms->id
        )) {
            $requiresTerms = true;
        }

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user->load(['primaryCampaign', 'activeSubscription.plan'])),
            'requires_terms_acceptance' => $requiresTerms,
        ]);
    }
}
