<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkQrRequest extends FormRequest
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
            'unique_code' => ['required', 'string', 'exists:qr_codes,unique_code'],
            'merchant_location_id' => ['required', 'exists:merchant_locations,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unique_code.required' => 'Please enter the QR code.',
            'unique_code.exists' => 'This QR code does not exist.',
            'merchant_location_id.required' => 'Please select a store location.',
            'merchant_location_id.exists' => 'The selected store location is invalid.',
        ];
    }
}
