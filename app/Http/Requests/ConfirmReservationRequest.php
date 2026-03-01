<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmReservationRequest extends FormRequest
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
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'plan_id' => ['sometimes', 'exists:subscription_plans,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'razorpay_payment_id.required' => 'Payment ID is missing from the response.',
            'razorpay_order_id.required' => 'Order ID is missing from the response.',
            'razorpay_signature.required' => 'Payment signature is missing.',
        ];
    }
}
