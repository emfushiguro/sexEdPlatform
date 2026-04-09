<?php

namespace App\Http\Requests\Admin;

use App\Enums\ParentChildModerationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectChildVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason_code' => [
                'required',
                Rule::in(ParentChildModerationReason::values()),
            ],
            'custom_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:reason_code,others',
            ],
            'issue_warning' => ['nullable', 'boolean'],
        ];
    }
}
