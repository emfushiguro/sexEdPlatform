<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Subscription ownership check is done in the controller
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:gcash,paymaya,grab_pay,card'],
            'accept_terms'   => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.in'       => 'Invalid payment method selected.',
            'accept_terms.required'   => 'You must accept the Terms & Conditions to proceed.',
            'accept_terms.accepted'   => 'You must accept the Terms & Conditions to proceed.',
        ];
    }
}
