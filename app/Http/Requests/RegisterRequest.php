<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim($this->first_name),
            'middle_initial' => $this->middle_initial ? strtoupper(trim($this->middle_initial)) : null,
            'last_name' => trim($this->last_name),
            'email' => strtolower(trim($this->email)),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix' => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(100)->format('Y-m-d'),
            ],
            'email' => [
                'required',
                'string',
                config('app.env') === 'testing' ? 'email:rfc' : 'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'ends_with:@gmail.com', // Gmail only for now
            ],
            'password' => [
                'required',
                'confirmed',
                config('app.env') === 'testing'
                    ? Password::min(8)->mixedCase()->numbers()->symbols()
                    : Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name can only contain letters and spaces.',
            'middle_initial.regex' => 'Middle initial can only contain letters and periods.',
            'last_name.regex' => 'Last name can only contain letters and spaces.',
            'suffix.in' => 'Please select a valid suffix.',
            'birthdate.required' => 'Birth date is required.',
            'birthdate.before' => 'Birth date must be before today.',
            'birthdate.after' => 'Invalid birth date. Must be within the last 100 years.',
            'email.email' => 'Please enter a valid Gmail address.',
            'email.unique' => 'This email address is already registered.',
            'email.ends_with' => 'Only Gmail addresses (@gmail.com) are accepted for registration.',
        ];
    }
}
