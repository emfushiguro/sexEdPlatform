<?php

namespace App\Http\Requests;

use App\Enums\InstructorApplicationRejectionReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectInstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'rejection_reason_code' => ['required', 'string', Rule::in(InstructorApplicationRejectionReason::values())],
            'rejection_reason_note' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn (): bool => (string) $this->input('rejection_reason_code') === InstructorApplicationRejectionReason::Other->value),
            ],
        ];
    }
}
