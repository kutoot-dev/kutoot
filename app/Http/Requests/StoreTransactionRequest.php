<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'merchant_location_id' => 'required|exists:merchant_locations,id',
            'amount' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ];
    }
}
