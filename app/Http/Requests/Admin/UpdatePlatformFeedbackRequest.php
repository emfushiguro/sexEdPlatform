<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformFeedbackStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PlatformFeedbackStatus::class)],
        ];
    }
}
