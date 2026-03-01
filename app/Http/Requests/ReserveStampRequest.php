<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReserveStampRequest extends FormRequest
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
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'campaign_id.required' => 'A campaign is required to reserve a stamp.',
            'campaign_id.exists' => 'The selected campaign does not exist.',
        ];
    }
}
