<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $length = (int) config('auth.otp_length', 6);

        return [
            'identifier' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'string', "size:{$length}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $length = (int) config('auth.otp_length', 6);

        return [
            'identifier.required' => 'Please enter your email or mobile number.',
            'otp.required' => 'Please enter the OTP.',
            'otp.size' => "OTP must be exactly {$length} digits.",
        ];
    }
}
