<?php

namespace App\Services\Persona;

final class PersonaGovernmentIdAttributes
{
    /**
     * Return only the provider fields needed for the local instructor review.
     *
     * @return array<string, string>
     */
    public function fromInquiry(array $inquiry): array
    {
        $references = (array) data_get($inquiry, 'data.relationships.verifications.data', []);
        $governmentId = null;
        foreach ($references as $reference) {
            if (is_array($reference) && ($reference['type'] ?? null) === 'verification/government-id') {
                $governmentId = is_string($reference['id'] ?? null) ? $reference['id'] : null;
                break;
            }
        }

        foreach ((array) ($inquiry['included'] ?? []) as $resource) {
            if (! is_array($resource) || ($resource['type'] ?? null) !== 'verification/government-id') {
                continue;
            }
            if ($governmentId !== null && ($resource['id'] ?? null) !== $governmentId) {
                continue;
            }

            return $this->allowlisted((array) ($resource['attributes'] ?? []));
        }

        return [];
    }

    /** @return array<string, string> */
    private function allowlisted(array $attributes): array
    {
        $fields = [];
        foreach ([
            'name-first' => ['name-first'],
            'name-middle' => ['name-middle'],
            'name-last' => ['name-last'],
            'birthdate' => ['birthdate'],
            'country-code' => ['country-code'],
            'id-class' => ['id-class'],
            'city' => ['city', 'address-city', 'normalized-address-city'],
            'subdivision' => ['subdivision', 'address-subdivision', 'normalized-address-subdivision'],
        ] as $output => $candidates) {
            foreach ($candidates as $candidate) {
                $value = $attributes[$candidate] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    $fields[$output] = trim($value);
                    break;
                }
            }
        }

        return $fields;
    }
}
