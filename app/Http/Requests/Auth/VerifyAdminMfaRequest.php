<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = $this->input('digits', []);
        $this->merge([
            'code' => is_array($digits) ? implode('', $digits) : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'digits' => ['required', 'array', 'size:6'],
            'digits.*' => ['required', 'regex:/^\d$/'],
            'code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'digits.required' => 'Enter the six-digit verification code.',
            'digits.size' => 'Enter all six digits.',
            'digits.*.regex' => 'The verification code may contain numbers only.',
            'code.digits' => 'Enter a valid six-digit verification code.',
        ];
    }
}
