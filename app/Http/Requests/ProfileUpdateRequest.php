<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\AccountEmail;
use App\Support\LearnerAgePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => AccountEmail::normalize($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf(fn (): bool => ! $this->learnerMayOmitEmail()),
                'nullable',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'current_password' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->emailChanged()),
                'current_password',
            ],
        ];
    }

    private function emailChanged(): bool
    {
        if (! $this->user()) {
            return false;
        }

        $submitted = is_string($this->input('email'))
            ? AccountEmail::normalize($this->input('email'))
            : null;
        $current = is_string($this->user()->email)
            ? AccountEmail::normalize($this->user()->email)
            : null;

        return $submitted !== $current;
    }

    private function learnerMayOmitEmail(): bool
    {
        $user = $this->user();

        return $user?->isLearner()
            && (LearnerAgePolicy::classify($user->birthdate) === LearnerAgePolicy::ELIGIBLE_TEENAGER
                || $user->hasAcceptedDependentAdultAuthorization());
    }
}
