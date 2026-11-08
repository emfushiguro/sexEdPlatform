<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminCreatorProfileRequest extends FormRequest
{
    private const EDITABLE_TABS = ['public', 'credentials'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => AccountEmail::normalize($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        return [
            'profile_tab' => ['nullable', Rule::in(self::EDITABLE_TABS)],
            'email' => [
                'required_if:profile_tab,credentials',
                'nullable',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'public_display_name' => ['required_unless:profile_tab,credentials', 'nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'affiliation' => ['required_unless:profile_tab,credentials', 'nullable', 'string', 'max:255'],
            'show_individual_attribution' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'current_password' => [
                'nullable',
                'prohibited_unless:profile_tab,credentials',
                Rule::requiredIf(fn (): bool => $this->credentialsEmailChanged() || filled($this->input('new_password'))),
                'current_password',
            ],
            'new_password' => [
                'nullable',
                'prohibited_unless:profile_tab,credentials',
                'confirmed',
                'different:current_password',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    private function credentialsEmailChanged(): bool
    {
        if ($this->input('profile_tab') !== 'credentials' || ! $this->user() || ! is_string($this->input('email'))) {
            return false;
        }

        return AccountEmail::normalize($this->input('email')) !== AccountEmail::normalize((string) $this->user()->email);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use.',
            'current_password.current_password' => 'The current password is incorrect.',
            'new_password.confirmed' => 'New password confirmation does not match.',
        ];
    }
}
