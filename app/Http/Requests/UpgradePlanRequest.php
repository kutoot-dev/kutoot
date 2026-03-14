<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpgradePlanRequest extends FormRequest
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
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'accepted_terms' => ['required', 'boolean', 'accepted'],
            'campaign_selections' => ['sometimes', 'array'],
            'campaign_selections.*.campaign_id' => ['required_with:campaign_selections', 'exists:campaigns,id'],
            'campaign_selections.*.stamp_count' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Please select a plan to upgrade to.',
            'plan_id.exists' => 'The selected plan does not exist.',
            'accepted_terms.required' => 'You must accept the terms and conditions.',
            'accepted_terms.accepted' => 'You must accept the terms and conditions to proceed.',
        ];
    }
}
