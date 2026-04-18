<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', Rule::in($this->reasonCodes())],
            'custom_reason' => ['nullable', 'string', 'max:3000', 'required_if:reason_code,others'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_code.required' => 'Please select an appropriate rejection reason.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function reasonCodes(): array
    {
        return [
            'age_not_suitable',
            'not_ready_for_topic',
            'others',
        ];
    }
}
