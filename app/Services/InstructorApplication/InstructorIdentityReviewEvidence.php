<?php

namespace App\Services\InstructorApplication;

use App\Models\InstructorIdentityVerification;
use App\Services\Persona\PersonaClient;
use App\Services\Persona\PersonaGovernmentIdAttributes;

final class InstructorIdentityReviewEvidence
{
    public function __construct(
        private readonly PersonaClient $personaClient,
        private readonly PersonaGovernmentIdAttributes $governmentIdAttributes,
    ) {}

    /**
     * Provider attributes are deliberately returned as an in-memory view value.
     *
     * @return array{attempt: mixed, provider_attributes: array<string, string>, provider_available: bool}
     */
    public function for(InstructorIdentityVerification $case): array
    {
        $attempt = $case->personaInquiries()
            ->current()
            ->latest('attempt_number')
            ->first();

        if (! $attempt?->persona_inquiry_id) {
            return [
                'attempt' => $attempt,
                'provider_attributes' => [],
                'provider_available' => false,
            ];
        }

        try {
            $inquiry = $this->personaClient->retrieveInquiry($attempt->persona_inquiry_id);
            $this->includeTypedVerifications($inquiry);
            $attributes = $this->governmentIdAttributes->fromInquiry($inquiry);

            return [
                'attempt' => $attempt,
                'provider_attributes' => $attributes,
                'provider_available' => true,
            ];
        } catch (\Throwable) {
            return [
                'attempt' => $attempt,
                'provider_attributes' => [],
                'provider_available' => false,
            ];
        }
    }

    private function includeTypedVerifications(array &$inquiry): void
    {
        $references = (array) data_get($inquiry, 'data.relationships.verifications.data', []);
        $included = (array) ($inquiry['included'] ?? []);

        foreach ($references as $reference) {
            if (! is_array($reference) || ! is_string($reference['id'] ?? null)) {
                continue;
            }

            $resource = match ($reference['type'] ?? null) {
                'verification/government-id' => $this->personaClient->retrieveGovernmentId($reference['id']),
                default => null,
            };
            if (is_array($resource) && is_array($resource['data'] ?? null)) {
                $included[] = $resource['data'];
            }
        }

        $inquiry['included'] = $included;
    }
}
