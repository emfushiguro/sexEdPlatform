<?php

namespace App\Services\Persona;

use App\Models\LearnerIdentityVerification;
use App\Models\InstructorIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Validation\ValidationException;

class PersonaTemplateSelector
{
    public function __construct(private readonly LearnerIdentityRequirement $requirement) {}

    /**
     * @return array{
     *   flow_type: string,
     *   template_key: string,
     *   template_id: string,
     *   local_document_type: string,
     *   local_government_id_type: ?string
     * }
     */
    public function select(
        User $user,
        ?LearnerIdentityVerification $case,
        string $documentType,
        ?string $governmentIdType,
    ): array {
        $this->assertAvailable();

        if ($user->isParentRegistration()) {
            if ($user->parent_verification_status === 'rejected' && ! $user->parent_identity_resubmission_allowed) {
                $this->invalid('This verification was closed. Contact support to continue.');
            }
            if ($case !== null || ($user->calculateAge() ?? 0) < 18) {
                $this->invalid('This account is not eligible for guardian verification.');
            }

            return $this->governmentSelection(
                PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
                $documentType,
                $governmentIdType,
            );
        }

        if (! $user->isLearner() || $case === null || (int) $case->user_id !== (int) $user->id) {
            $this->invalid('This account is not eligible for learner verification.');
        }

        try {
            $pathway = $this->requirement->pathwayFor($user);
        } catch (\DomainException) {
            $this->invalid('This account is not eligible for Persona verification.');
        }

        if ($case->superseded_at !== null
            || ($case->status === 'rejected' && ! $case->resubmission_allowed)
            || $case->pathway !== $pathway
            || in_array($case->status, ['pending', 'approved'], true)) {
            $this->invalid('This identity case cannot start a new Persona attempt.');
        }

        if ($pathway === 'teen' && in_array($documentType, ['school_id', 'institution_id'], true)) {
            if ($governmentIdType !== null) {
                $this->invalid('A school or institution identity does not use a government ID choice.');
            }

            return [
                'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL,
                'template_key' => PersonaInquiry::TEMPLATE_SELFIE,
                'template_id' => $this->template(PersonaInquiry::TEMPLATE_SELFIE),
                'local_document_type' => $documentType,
                'local_government_id_type' => null,
            ];
        }

        if ($documentType !== 'government_id') {
            $this->invalid('This learner pathway requires a supported government ID.');
        }

        return $this->governmentSelection(
            $pathway === 'teen' ? PersonaInquiry::FLOW_TEEN_GOVERNMENT : PersonaInquiry::FLOW_ADULT_GOVERNMENT,
            $documentType,
            $governmentIdType,
        );
    }

    /**
     * @return array{
     *   flow_type: string,
     *   template_key: string,
     *   template_id: string,
     *   local_document_type: string,
     *   local_government_id_type: ?string
     * }
     */
    public function selectForInstructor(
        User $user,
        InstructorIdentityVerification $case,
        string $governmentIdType,
    ): array {
        $this->assertAvailable();

        if (! $user->isInstructorApplicant()
            || (int) $case->user_id !== (int) $user->id
            || $case->source !== InstructorIdentityVerification::SOURCE_DIRECT_PERSONA
            || $case->superseded_at !== null) {
            $this->invalid('This account is not eligible for instructor verification.');
        }

        return $this->governmentSelection(
            PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT,
            'government_id',
            $governmentIdType,
        );
    }

    private function governmentSelection(string $flow, string $documentType, ?string $governmentIdType): array
    {
        if ($documentType !== 'government_id' || ! is_string($governmentIdType) || $governmentIdType === '') {
            $this->invalid('A supported government ID choice is required.');
        }

        $mappedClasses = (array) config('persona.government_id_classes', []);
        if (! array_key_exists($governmentIdType, $mappedClasses)
            || ! array_key_exists($governmentIdType, (array) config('guardian_identity.id_types', []))) {
            $this->invalid('That government ID cannot be verified in Persona Sandbox.');
        }

        return [
            'flow_type' => $flow,
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => $this->template(PersonaInquiry::TEMPLATE_GOVID_SELFIE),
            'local_document_type' => 'government_id',
            'local_government_id_type' => $governmentIdType,
        ];
    }

    private function template(string $key): string
    {
        $template = config('persona.templates.'.$key);
        if (! is_string($template) || trim($template) === '') {
            $this->invalid('Persona verification is not configured for this identity path.');
        }

        return $template;
    }

    private function assertAvailable(): void
    {
        if (! config('persona.enabled', false) || config('persona.environment', 'sandbox') !== 'sandbox') {
            $this->invalid('Persona verification is not available.');
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['persona' => $message]);
    }
}
