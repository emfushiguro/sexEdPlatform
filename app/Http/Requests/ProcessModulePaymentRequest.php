<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessModulePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:gcash,paymaya,grab_pay,card'],
            'accept_terms' => ['required', 'accepted'],
            'billing_name' => ['required', 'string', 'max:120'],
            'billing_email' => ['required', 'email', 'max:150'],
            'billing_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.in' => 'Invalid payment method selected.',
            'accept_terms.required' => 'You must accept the Terms & Conditions to proceed.',
            'accept_terms.accepted' => 'You must accept the Terms & Conditions to proceed.',
            'billing_name.required' => 'Billing name is required.',
            'billing_email.required' => 'Billing email is required.',
            'billing_email.email' => 'Please enter a valid billing email address.',
        ];
    }
}
