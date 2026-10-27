<?php

namespace App\Services\Persona;

use App\Models\PersonaInquiry;
use Carbon\CarbonImmutable;

class PersonaPolicyEvaluator
{
    /**
     * @return array{
     *   persona_status: string,
     *   verification_outcome: string,
     *   government_id_verification_id: ?string,
     *   selfie_verification_id: ?string,
     *   country_code: ?string,
     *   id_class: ?string,
     *   country_policy_status: string,
     *   document_policy_status: string,
     *   completed_at: ?CarbonImmutable,
     *   expired_at: ?CarbonImmutable
     * }
     */
    public function evaluate(PersonaInquiry $attempt, array $inquiry): array
    {
        $attributes = (array) data_get($inquiry, 'data.attributes', []);
        $status = strtolower((string) ($attributes['status'] ?? ''));
        $completedAt = $this->date($attributes['completed-at'] ?? $attributes['completed_at'] ?? null);
        $expiredAt = $this->date($attributes['expired-at'] ?? $attributes['expired_at'] ?? null);
        $references = (array) data_get($inquiry, 'data.relationships.verifications.data', []);
        $resources = (array) ($inquiry['included'] ?? []);
        $governmentReference = $this->reference($references, 'verification/government-id');
        $selfieReference = $this->reference($references, 'verification/selfie');
        $government = $this->resource($resources, 'verification/government-id', $governmentReference['id'] ?? null);
        $selfie = $this->resource($resources, 'verification/selfie', $selfieReference['id'] ?? null);

        $governmentId = $governmentReference['id'] ?? $government['id'] ?? null;
        $selfieId = $selfieReference['id'] ?? $selfie['id'] ?? null;
        $country = $this->normalized($government['attributes']['country-code'] ?? null);
        $idClass = $this->normalized($government['attributes']['id-class'] ?? null);
        $countryPolicy = PersonaInquiry::POLICY_NOT_APPLICABLE;
        $documentPolicy = PersonaInquiry::POLICY_NOT_APPLICABLE;

        if ($attempt->flow_type !== PersonaInquiry::FLOW_TEEN_SCHOOL) {
            $countryPolicy = $this->countryPolicy($country);
            $documentPolicy = $this->documentPolicy($attempt, $idClass);
        }

        $outcome = $this->outcome(
            $attempt,
            $status,
            $government,
            $selfie,
            $countryPolicy,
            $documentPolicy,
        );

        return [
            'persona_status' => $status,
            'verification_outcome' => $outcome,
            'government_id_verification_id' => is_string($governmentId) ? $governmentId : null,
            'selfie_verification_id' => is_string($selfieId) ? $selfieId : null,
            'country_code' => $country,
            'id_class' => $idClass,
            'country_policy_status' => $countryPolicy,
            'document_policy_status' => $documentPolicy,
            'completed_at' => $completedAt,
            'expired_at' => $expiredAt,
        ];
    }

    private function outcome(
        PersonaInquiry $attempt,
        string $status,
        array $government,
        array $selfie,
        string $countryPolicy,
        string $documentPolicy,
    ): string {
        if (in_array($status, ['failed', 'expired'], true)) {
            return PersonaInquiry::OUTCOME_RETRYABLE;
        }
        if ($status === 'declined') {
            return PersonaInquiry::OUTCOME_INELIGIBLE;
        }
        if ($status === 'needs-review') {
            return PersonaInquiry::OUTCOME_INCONCLUSIVE;
        }
        if (! in_array($status, ['completed', 'approved'], true)) {
            return $status === '' ? PersonaInquiry::OUTCOME_INCONCLUSIVE : PersonaInquiry::OUTCOME_NOT_READY;
        }

        if ($attempt->flow_type === PersonaInquiry::FLOW_TEEN_SCHOOL) {
            return $this->resourceOutcome($selfie) === 'passed'
                ? PersonaInquiry::OUTCOME_ELIGIBLE
                : ($this->resourceOutcome($selfie) === 'failed'
                    ? PersonaInquiry::OUTCOME_INELIGIBLE
                    : PersonaInquiry::OUTCOME_INCONCLUSIVE);
        }

        if ($countryPolicy !== PersonaInquiry::POLICY_ELIGIBLE
            || $documentPolicy !== PersonaInquiry::POLICY_ELIGIBLE) {
            return ($countryPolicy === PersonaInquiry::POLICY_INELIGIBLE
                || $documentPolicy === PersonaInquiry::POLICY_INELIGIBLE)
                ? PersonaInquiry::OUTCOME_INELIGIBLE
                : PersonaInquiry::OUTCOME_INCONCLUSIVE;
        }

        $governmentOutcome = $this->resourceOutcome($government);
        $selfieOutcome = $this->resourceOutcome($selfie);
        if ($governmentOutcome === 'passed' && $selfieOutcome === 'passed') {
            return PersonaInquiry::OUTCOME_ELIGIBLE;
        }
        if ($governmentOutcome === 'failed' || $selfieOutcome === 'failed') {
            return PersonaInquiry::OUTCOME_INELIGIBLE;
        }

        return PersonaInquiry::OUTCOME_INCONCLUSIVE;
    }

    private function countryPolicy(?string $country): string
    {
        if ($country === null) {
            return PersonaInquiry::POLICY_INCONCLUSIVE;
        }

        return $country === 'PH' ? PersonaInquiry::POLICY_ELIGIBLE : PersonaInquiry::POLICY_INELIGIBLE;
    }

    private function documentPolicy(PersonaInquiry $attempt, ?string $idClass): string
    {
        if ($idClass === null) {
            return PersonaInquiry::POLICY_INCONCLUSIVE;
        }

        $expected = data_get(config('persona.government_id_classes', []), $attempt->local_government_id_type);
        if (! is_string($expected)) {
            return PersonaInquiry::POLICY_INELIGIBLE;
        }

        return $idClass === strtolower($expected)
            ? PersonaInquiry::POLICY_ELIGIBLE
            : PersonaInquiry::POLICY_INELIGIBLE;
    }

    private function reference(array $references, string $type): array
    {
        foreach ($references as $reference) {
            if (is_array($reference) && ($reference['type'] ?? null) === $type) {
                return $reference;
            }
        }

        return [];
    }

    private function resource(array $resources, string $type, ?string $id): array
    {
        foreach ($resources as $resource) {
            if (! is_array($resource) || ($resource['type'] ?? null) !== $type) {
                continue;
            }
            if ($id === null || ($resource['id'] ?? null) === $id) {
                return $resource;
            }
        }

        return [];
    }

    private function resourceOutcome(array $resource): ?string
    {
        $status = $resource['attributes']['status'] ?? null;

        return is_string($status) ? strtolower($status) : null;
    }

    private function normalized(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return strtoupper(trim($value)) === 'PH' ? 'PH' : strtolower(trim($value));
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
