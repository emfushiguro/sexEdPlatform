<?php

namespace App\Http\Requests\Auth;

class SubmitLearnerIdentitySelfieRequest extends SubmitLearnerIdentityRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $draft = session('learner_identity_document_draft', []);
        $case = $this->user()?->identityVerifications()->whereNull('superseded_at')->first();
        if (config('persona.enabled') && $case?->pathway === 'teen'
            && in_array($draft['document_type'] ?? null, ['school_id', 'institution_id'], true)) {
            $attempt = app(\App\Services\Persona\PersonaInquiryService::class)->currentAttempt($this->user());
            $rules['selfie'] = ['prohibited'];
            $rules['confirm_submission'][] = function ($attribute, $value, $fail) use ($attempt, $draft): void {
                if (! $attempt?->isPolicyEligible() || $attempt->local_document_type !== $draft['document_type']) {
                    $fail('Complete selfie verification before submitting for review.');
                }
            };
        }
        if (($draft['document_type'] ?? null) === 'government_id' && isset($rules['selfie'])) {
            $rules['selfie'][0] = 'required';
        }

        return array_intersect_key($rules, array_flip(['selfie', 'confirm_submission', 'submission_round']));
    }
}
