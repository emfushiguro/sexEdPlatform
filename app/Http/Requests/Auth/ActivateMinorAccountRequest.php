<?php

namespace App\Http\Requests\Auth;

use App\Models\MinorAccountHandoff;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActivateMinorAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $handoff = app(MinorAccountHandoffService::class)->findUsable(
            (string) $this->input('code'),
            MinorAccountHandoff::PURPOSE_ACTIVATION,
        );
        $profileId = $handoff?->child?->learnerProfile?->id;

        return [
            'code' => ['required', 'string', 'max:32'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('learner_profiles', 'username')->ignore($profileId),
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'privacy_notice' => ['accepted'],
        ];
    }
}
