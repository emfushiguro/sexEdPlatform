<?php

namespace App\Http\Requests;

use App\Enums\PlatformFeedbackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PlatformFeedbackType::class)],
            'submission_token' => ['nullable', 'uuid'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:500'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'may_contact' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', new \App\Rules\ValidSupportImage],
        ];
    }
}
