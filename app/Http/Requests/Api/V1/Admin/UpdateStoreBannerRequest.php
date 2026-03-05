<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-store-banner');
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:' . config('upload.max_file_size_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'link_url.url' => 'The link URL must be a valid URL.',
            'image.max' => 'The image exceeds the maximum allowed file size.',
            'image.mimes' => 'The image must be a JPEG, PNG, or WebP file.',
        ];
    }
}
