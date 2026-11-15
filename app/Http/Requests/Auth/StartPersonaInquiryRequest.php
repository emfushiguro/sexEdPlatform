<?php

namespace App\Http\Requests\Auth;

use App\Support\LearnerAgePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPersonaInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user?->isLearner()) {
            return false;
        }
        if ($user->isParentRegistration()) {
            return $user->hasVerifiedEmail();
        }

        return match (LearnerAgePolicy::classify($user->birthdate)) {
            LearnerAgePolicy::ELIGIBLE_TEENAGER => $user->hasCompletedMinorActivation()
                && $user->qualifyingMinorGuardianRelationship() !== null,
            LearnerAgePolicy::ELIGIBLE_ADULT => $user->hasVerifiedEmail() || $user->hasAcceptedDependentAdultAuthorization(),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['school_id', 'institution_id', 'government_id', 'assisted_review'])],
            'government_id_type' => ['nullable', 'string', 'max:40', 'required_if:document_type,government_id'],
        ];
    }
}
