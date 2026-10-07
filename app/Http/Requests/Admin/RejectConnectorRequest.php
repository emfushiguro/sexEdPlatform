<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:1'],
            'reason_code' => [
                'required',
                'string',
                Rule::in([
                    'ineligible',
                    'fraudulent',
                    'policy_violation',
                    'duplicate',
                    'unsupported',
                    'other',
                ]),
            ],
            'applicant_reason' => ['required', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
