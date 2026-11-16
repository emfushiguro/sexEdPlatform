<?php

namespace App\Http\Requests;

use App\Support\AccountEmail;
use App\Support\LearnerAgePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AccountInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->input('email'))
                ? AccountEmail::normalize($this->input('email'))
                : $this->input('email'),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $birthdate = session('pending_personal_info.birthdate');
            if (LearnerAgePolicy::classify($birthdate) !== LearnerAgePolicy::ELIGIBLE_ADULT) {
                $validator->errors()->add('birthdate', 'Teenager accounts must use guardian-assisted registration.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
