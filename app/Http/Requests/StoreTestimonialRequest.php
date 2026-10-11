<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'submission_token' => ['nullable', 'uuid'],
            'quotation' => ['required', 'string', 'max:1000'],
            'show_role' => ['nullable', 'boolean'],
            'show_profile_image' => ['nullable', 'boolean'],
            'consent' => ['accepted'],
        ];
    }
}
