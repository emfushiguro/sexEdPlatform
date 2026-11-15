<?php

namespace App\Http\Requests\Auth;

use App\Support\AccountEmail;
use Illuminate\Foundation\Http\FormRequest;

class StoreMinorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('guardian_email'))) {
            $this->merge(['guardian_email' => AccountEmail::normalize($this->input('guardian_email'))]);
        }
    }

    public function rules(): array
    {
        $authenticated = $this->user() !== null;

        return [
            'first_name' => [$authenticated ? 'nullable' : 'required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'last_name' => [$authenticated ? 'nullable' : 'required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'birthdate' => [$authenticated ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
            'guardian_email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255'],
        ];
    }
}
