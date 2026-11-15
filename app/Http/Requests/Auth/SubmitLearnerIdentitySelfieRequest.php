<?php

namespace App\Http\Requests\Auth;

use App\Services\Persona\PersonaInquiryService;
use DomainException;

class SubmitLearnerIdentitySelfieRequest extends SubmitLearnerIdentityRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $draft = session('learner_identity_document_draft', []);
        $case = $this->user()?->identityVerifications()->whereNull('superseded_at')->first();
        $rules['confirm_submission'][] = function ($attribute, $value, $fail) use ($case, $draft): void {
            if (! $case || empty($draft['document_type'])) {
                $fail('Save your identity evidence before camera verification.');
                return;
            }
            try {
                app(PersonaInquiryService::class)->assertLearnerMaySubmit(
                    $this->user(), $case, $draft['document_type'], $draft['government_id_type'] ?? null,
                );
            } catch (DomainException $exception) {
                $fail($exception->getMessage());
            }
        };

        return array_intersect_key($rules, array_flip(['selfie', 'confirm_submission', 'submission_round', 'learner_note']));
    }
}
