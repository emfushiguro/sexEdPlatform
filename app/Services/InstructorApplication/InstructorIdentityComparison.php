<?php

namespace App\Services\InstructorApplication;

use DateTimeImmutable;

final class InstructorIdentityComparison
{
    private const NORMALIZATION_VERSION = 'v1';

    /**
     * Compare only the declared fields and documented Persona attributes.
     *
     * @return array{normalization_version:string, fields:array<string, array{outcome:string}>}
     */
    public function compare(array $declared, array $provider): array
    {
        $fields = [
            'first_name' => ['applicant' => 'first_name', 'provider' => 'name-first', 'normalizer' => 'name'],
            'middle_initial' => ['applicant' => 'middle_initial', 'provider' => 'name-middle', 'normalizer' => 'name'],
            'last_name' => ['applicant' => 'last_name', 'provider' => 'name-last', 'normalizer' => 'name'],
            'suffix' => ['applicant' => 'suffix', 'provider' => 'name-suffix', 'normalizer' => 'name'],
            'birthdate' => ['applicant' => 'birthdate', 'provider' => 'birthdate', 'normalizer' => 'date'],
            'city_name' => ['applicant' => 'city_name', 'provider' => 'city', 'normalizer' => 'name'],
            'barangay_name' => ['applicant' => 'barangay_name', 'provider' => 'subdivision', 'normalizer' => 'name'],
        ];

        $summary = [];
        foreach ($fields as $field => $definition) {
            if ($field === 'suffix' && ! array_key_exists($definition['provider'], $provider)) {
                $summary[$field] = ['outcome' => 'not_comparable'];
                continue;
            }

            $applicantValue = $declared[$definition['applicant']] ?? null;
            $providerValue = $provider[$definition['provider']] ?? null;
            $summary[$field] = ['outcome' => $this->outcome($applicantValue, $providerValue, $definition['normalizer'])];
        }

        return [
            'normalization_version' => self::NORMALIZATION_VERSION,
            'fields' => $summary,
        ];
    }

    private function outcome(mixed $applicant, mixed $provider, string $normalizer): string
    {
        $applicantMissing = $applicant === null || trim((string) $applicant) === '';
        $providerMissing = $provider === null || trim((string) $provider) === '';

        if ($applicantMissing && $providerMissing) {
            return 'unavailable';
        }
        if ($applicantMissing) {
            return 'missing_from_applicant';
        }
        if ($providerMissing) {
            return 'missing_from_persona';
        }

        $left = $normalizer === 'date' ? $this->date((string) $applicant) : $this->name((string) $applicant);
        $right = $normalizer === 'date' ? $this->date((string) $provider) : $this->name((string) $provider);

        if ($left === null || $right === null) {
            return 'unavailable';
        }

        return $left === $right ? 'match' : 'mismatch';
    }

    private function name(string $value): ?string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? '';
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        return $normalized !== '' ? $normalized : null;
    }

    private function date(string $value): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date && $date->format('Y-m-d') === trim($value) ? $date->format('Y-m-d') : null;
    }
}
