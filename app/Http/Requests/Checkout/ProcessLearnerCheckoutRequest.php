<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ProcessLearnerCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'string', 'in:gcash,paymaya,grab_pay,card'],
            'accept_terms' => ['required', 'accepted'],
            'billing_name' => ['nullable', 'string', 'max:120'],
            'billing_email' => ['nullable', 'email', 'max:150'],
            'billing_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.in' => 'Invalid payment method selected.',
            'accept_terms.required' => 'You must accept the Terms & Conditions to proceed.',
            'accept_terms.accepted' => 'You must accept the Terms & Conditions to proceed.',
            'billing_email.email' => 'Please enter a valid billing email address.',
        ];
    }
}
