<?php

namespace App\Http\Requests\Api\V1\Seller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMerchantLocationProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:1000'],
            'gst_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pan_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'state_id' => ['sometimes', 'nullable', 'integer', 'exists:'.config('world.connection', 'mysql').'.states,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:'.config('world.connection', 'mysql').'.cities,id'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['image', 'max:' . config('upload.max_file_size_kb')],
        ];
    }
}
