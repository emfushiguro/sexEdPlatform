<?php

namespace App\Http\Requests\Auth;

use App\Models\MinorAccountHandoff;
use App\Services\Auth\MinorAccountHandoffService;
use App\Support\LearnerAgePolicy;
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
        $adultDependent = $handoff && LearnerAgePolicy::classify($handoff->child?->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT;
        $consentMethod = (string) $this->input('consent_method', 'self');
        $methods = ['self'];
        if ($adultDependent && $handoff->relationship && app(MinorAccountHandoffService::class)->approvedAdultAuthority($handoff->relationship)) {
            $methods[] = 'representative';
        }

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
            'privacy_notice' => $adultDependent && $consentMethod === 'representative' ? ['sometimes', 'accepted'] : ['accepted'],
            'adult_consent' => [$adultDependent && $consentMethod === 'self' ? 'accepted' : 'prohibited'],
            'consent_method' => $adultDependent ? ['required', Rule::in($methods)] : ['prohibited'],
        ];
    }
}
