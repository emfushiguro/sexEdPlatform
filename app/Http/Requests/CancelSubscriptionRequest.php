<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
