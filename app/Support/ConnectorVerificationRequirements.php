<?php

namespace App\Support;

use InvalidArgumentException;

final class ConnectorVerificationRequirements
{
    private const MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function organizationTypes(): array
    {
        return config('connector_verification.organization_types', []);
    }

    public function isCompatible(string $category, string $organizationType): bool
    {
        $type = $this->organizationTypes()[$organizationType] ?? null;

        return is_array($type)
            && in_array($category, $type['categories'] ?? [], true);
    }

    public function requirementsFor(string $category, string $organizationType): array
    {
        if (! isset($this->organizationTypes()[$organizationType])) {
            throw new InvalidArgumentException('Unsupported organization type.');
        }

        if (! $this->isCompatible($category, $organizationType)) {
            throw new InvalidArgumentException('Organization type is not compatible with the selected category.');
        }

        $definitions = config('connector_verification.requirements', []);
        $keys = $this->organizationTypes()[$organizationType]['requirements'] ?? [];

        return array_values(array_map(
            function (string $key) use ($definitions): array {
                $definition = $definitions[$key] ?? null;

                if (! is_array($definition)) {
                    throw new InvalidArgumentException("Unknown verification requirement [{$key}].");
                }

                return $this->normalize($key, $definition);
            },
            array_values(array_unique($keys)),
        ));
    }

    public function snapshotFor(string $category, string $organizationType): array
    {
        return array_map(
            static fn (array $requirement): array => array_replace([], $requirement),
            $this->requirementsFor($category, $organizationType),
        );
    }

    private function normalize(string $key, array $definition): array
    {
        $mimeTypes = $definition['accepted_mime_types'] ?? self::MIME_TYPES;
        $mimeTypes = array_values(array_intersect($mimeTypes, self::MIME_TYPES));

        return [
            'key' => $key,
            'label' => (string) ($definition['label'] ?? str($key)->headline()),
            'guidance' => (string) ($definition['guidance'] ?? ''),
            'evidence_category' => (string) ($definition['evidence_category'] ?? 'supporting_evidence'),
            'required' => (bool) ($definition['required'] ?? false),
            'rule_group' => (string) ($definition['rule_group'] ?? $key),
            'rule_mode' => (string) ($definition['rule_mode'] ?? 'all'),
            'minimum_evidence_count' => max(1, (int) ($definition['minimum_evidence_count'] ?? 1)),
            'maximum_evidence_count' => max(1, (int) ($definition['maximum_evidence_count'] ?? 1)),
            'accepted_mime_types' => $mimeTypes ?: self::MIME_TYPES,
            'max_kilobytes' => min(10240, max(1, (int) ($definition['max_kilobytes'] ?? 10240))),
            'metadata_fields' => array_values(array_intersect(
                ['issuer', 'document_number', 'issue_date', 'expiry_date'],
                array_values($definition['metadata_fields'] ?? []),
            )),
            'expires' => (bool) ($definition['expires'] ?? false),
            'manual_not_applicable_allowed' => (bool) ($definition['manual_not_applicable_allowed'] ?? false),
            'issuing_authority' => $definition['issuing_authority'] ?? null,
            'source_url' => $definition['source_url'] ?? null,
            'source_reviewed_on' => $definition['source_reviewed_on'] ?? null,
        ];
    }
}
