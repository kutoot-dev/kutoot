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
        $worldConnection = config('world.connection');

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
            'country_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.countries", 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.states", 'id')],
            'city_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.cities", 'id')],
            'pin_code' => ['nullable', 'string', 'max:20'],
            'full_address' => ['nullable', 'string', 'max:1000'],
            'profile_picture' => ['nullable', 'image', 'max:' . config('upload.max_file_size_kb')],
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
