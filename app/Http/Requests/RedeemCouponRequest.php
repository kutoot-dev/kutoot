<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemCouponRequest extends FormRequest
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
            'merchant_location_id' => ['required', 'exists:merchant_locations,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'merchant_location_id.required' => 'Please select a store location.',
            'merchant_location_id.exists' => 'The selected store location is invalid.',
            'amount.required' => 'Please enter the bill amount.',
            'amount.min' => 'The bill amount must be at least ₹0.01.',
        ];
    }
}
