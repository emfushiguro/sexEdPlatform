<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'The selected plan is no longer available.',
        ];
    }
}
