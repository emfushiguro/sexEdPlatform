<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSuspensionAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appeal_reason' => ['required', 'string', 'min:10', 'max:5000'],
            'evidence_payload' => ['nullable', 'array'],
        ];
    }
}
