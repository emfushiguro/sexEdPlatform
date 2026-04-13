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
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('reason_code') !== ParentChildModerationReason::Others->value) {
                        return;
                    }

                    $plainText = trim((string) preg_replace('/\x{00a0}/u', ' ', html_entity_decode(strip_tags((string) $value))));

                    if ($plainText === '') {
                        $fail('Please provide a custom rejection reason.');
                    }
                },
            ],
            'issue_warning' => ['nullable', 'boolean'],
        ];
    }
}
