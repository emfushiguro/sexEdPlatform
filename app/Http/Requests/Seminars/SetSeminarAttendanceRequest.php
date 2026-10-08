<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;

class SetSeminarAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attended' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
