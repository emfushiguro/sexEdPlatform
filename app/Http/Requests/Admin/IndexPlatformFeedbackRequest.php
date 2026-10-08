<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlatformFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return ['status' => ['nullable', Rule::enum(PlatformFeedbackStatus::class)], 'type' => ['nullable', Rule::enum(PlatformFeedbackType::class)], 'rating' => ['nullable', 'integer', 'between:1,5'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'search' => ['nullable', 'string', 'max:100']];
    }
}
