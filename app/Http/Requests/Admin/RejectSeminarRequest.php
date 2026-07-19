<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(array_keys(config('seminars.rejection_reasons', [])))],
            'note' => ['required_if:reason,other', 'nullable', 'string', 'max:2000'],
        ];
    }
}
