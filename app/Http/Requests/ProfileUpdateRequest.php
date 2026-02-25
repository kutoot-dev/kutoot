<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
                'required_without:mobile',
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(User::class)->ignore($this->user()->id),
                'required_without:email',
            ],
            'gender' => ['nullable', 'in:male,female,other'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'pin_code' => ['nullable', 'string', 'max:20'],
            'full_address' => ['nullable', 'string', 'max:1000'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Please provide an email or mobile number.',
            'mobile.required_without' => 'Please provide a mobile number or email.',
        ];
    }
}
