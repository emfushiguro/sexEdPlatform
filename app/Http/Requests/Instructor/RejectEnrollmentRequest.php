<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class RejectEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason_code' => ['required', 'string', 'max:100'],
            'rejection_reason_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
