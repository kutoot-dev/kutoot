<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(public OtpService $otpService) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'status' => session('status'),
            'debugOtp' => session('debugOtp'),
        ]);
    }

    /**
     * Send OTP for registration.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'mobile' => 'required|string|max:15|unique:'.User::class,
        ]);

        // Store registration data in session for later use
        $request->session()->put('register_data', [
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
        ]);

        // Create a temporary user record to generate OTP, or use session
        $otp = $this->otpService->generateOtpForSession($request->mobile);

        $this->otpService->sendOtp(null, $otp, 'mobile', $request->mobile);

        $flash = ['status' => 'OTP sent to '.$request->mobile.'.'];

        if (config('app.debug')) {
            $flash['debugOtp'] = $otp;
        }

        return back()->with($flash);
    }

    /**
     * Verify OTP and complete registration.
     */
    public function verifyAndRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $registerData = $request->session()->get('register_data');

        if (! $registerData) {
            throw ValidationException::withMessages([
                'otp' => __('Registration session expired. Please start over.'),
            ]);
        }

        $mobile = $registerData['mobile'];

        if (! $this->otpService->verifyOtpFromSession($mobile, $request->otp)) {
            throw ValidationException::withMessages([
                'otp' => __('Invalid or expired OTP. Please try again.'),
            ]);
        }

        $user = User::create([
            'name' => $registerData['name'],
            'email' => $registerData['email'],
            'mobile' => $mobile,
        ]);

        $request->session()->forget('register_data');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
