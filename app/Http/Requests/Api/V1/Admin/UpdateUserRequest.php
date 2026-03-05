<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-user');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $worldConnection = config('world.connection');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')), 'required_without:mobile'],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($this->route('user')), 'required_without:email'],
            'password' => ['sometimes', 'string', 'min:8'],
            'email_verified_at' => ['nullable', 'date'],
            'primary_campaign_id' => ['nullable', 'exists:campaigns,id'],
            'gender' => ['nullable', 'in:male,female,other'],
            'country_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.countries", 'id')],
            'state_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.states", 'id')],
            'city_id' => ['nullable', 'integer', Rule::exists("{$worldConnection}.cities", 'id')],
            'pin_code' => ['nullable', 'string', 'max:20'],
            'full_address' => ['nullable', 'string', 'max:1000'],
            'profile_picture' => ['nullable', 'image', 'max:' . config('upload.max_file_size_kb')],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }
}
