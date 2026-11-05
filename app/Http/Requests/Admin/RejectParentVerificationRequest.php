<?php

namespace App\Http\Requests\Admin;

use App\Enums\ParentChildModerationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectParentVerificationRequest extends FormRequest
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
            'allow_resubmission' => ['sometimes', 'boolean'],
            'requested_corrections' => [
                Rule::requiredIf(fn (): bool => $this->boolean('allow_resubmission', true)),
                'array',
                'min:1',
            ],
            'requested_corrections.*' => [
                'required',
                'string',
                'distinct',
                Rule::in($this->requestedCorrectionTypes()),
            ],
            'persona_attempt_id' => [$this->route('user')?->parent_identity_evidence_source === 'persona' ? 'required' : 'nullable', 'integer', 'min:1'],
        ];
    }

    private function requestedCorrectionTypes(): array
    {
        return $this->route('user')?->parent_identity_evidence_source === 'persona'
            ? ['persona_identity']
            : ['id'];
    }
}
