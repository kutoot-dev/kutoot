<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-news-article');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:' . config('upload.max_file_size_kb')],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A news article title is required.',
            'link_url.url' => 'The link URL must be a valid URL.',
            'image.max' => 'The image exceeds the maximum allowed file size.',
            'image.mimes' => 'The image must be a JPEG, PNG, or WebP file.',
            'description.max' => 'The description must not exceed 5000 characters.',
        ];
    }
}
