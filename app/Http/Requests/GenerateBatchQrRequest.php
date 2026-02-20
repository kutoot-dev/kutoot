<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBatchQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'count' => ['required', 'integer', 'min:1', 'max:1000'],
            'prefix' => ['sometimes', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'count.required' => 'Please specify the number of QR codes to generate.',
            'count.min' => 'You must generate at least 1 QR code.',
            'count.max' => 'You can generate a maximum of 1,000 QR codes at once.',
            'prefix.max' => 'The prefix must not exceed 10 characters.',
        ];
    }
}
