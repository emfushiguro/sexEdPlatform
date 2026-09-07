<?php

namespace App\Support;

final class GuardianRelationshipTypes
{
    public const OTHER = 'other';
    public const LEGACY_PARENT = 'parent';

    public static function options(): array
    {
        return [
            'biological_mother' => 'Biological Mother',
            'biological_father' => 'Biological Father',
            'adoptive_parent' => 'Adoptive Parent',
            'foster_parent' => 'Foster Parent',
            'grandmother' => 'Grandmother',
            'grandfather' => 'Grandfather',
            'aunt' => 'Aunt',
            'uncle' => 'Uncle',
            'older_sister' => 'Older Sister',
            'older_brother' => 'Older Brother',
            'legal_guardian' => 'Legal Guardian',
            'court_appointed_guardian' => 'Court-Appointed Guardian',
            'relative' => 'Relative',
            'family_friend' => 'Family Friend',
            'caregiver' => 'Caregiver',
            self::OTHER => 'Other',
            self::LEGACY_PARENT => 'Parent',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::options());
    }

    public static function selectableValues(): array
    {
        return array_values(array_filter(
            self::values(),
            static fn (string $type): bool => $type !== self::LEGACY_PARENT,
        ));
    }

    public static function label(?string $type, ?string $custom = null): string
    {
        if ($type === self::OTHER && filled($custom)) {
            return trim((string) $custom);
        }

        return self::options()[$type ?: self::LEGACY_PARENT] ?? 'Parent';
    }

    public static function requiresVerification(?string $type): bool
    {
        return $type !== self::LEGACY_PARENT && array_key_exists((string) $type, config('guardian_relationships.types', []));
    }

    public static function pathway(?string $type): string
    {
        if ($type === self::LEGACY_PARENT) {
            return 'legacy';
        }

        return (string) config("guardian_relationships.types.{$type}.pathway", 'custom_care');
    }

    public static function pathwayLabel(?string $type): string
    {
        $pathway = self::pathway($type);

        return (string) config("guardian_relationships.pathways.{$pathway}.label", 'Relationship Evidence Review');
    }

    public static function initialVerificationStatus(?string $type): string
    {
        return $type === self::LEGACY_PARENT ? 'reserved' : 'pending';
    }

    public static function acceptedDocumentTypes(?string $type): array
    {
        $pathway = self::pathway($type);
        $keys = (array) config("guardian_relationships.pathways.{$pathway}.document_types", []);
        $labels = (array) config('guardian_relationships.document_types', []);

        return array_values(array_filter($keys, static fn (string $key): bool => array_key_exists($key, $labels)));
    }

    public static function requiredDocumentTypes(?string $type): array
    {
        return (array) config('guardian_relationships.pathways.'.self::pathway($type).'.required_any_of', []);
    }

    public static function requiresCircumstances(?string $type): bool
    {
        return (bool) config('guardian_relationships.pathways.'.self::pathway($type).'.requires_circumstances', false);
    }

    public static function documentTypeOptions(?string $type): array
    {
        return array_intersect_key(
            (array) config('guardian_relationships.document_types', []),
            array_flip(self::acceptedDocumentTypes($type)),
        );
    }

    public static function statusLabel(?string $status): string
    {
        return (string) config("guardian_relationships.verification_statuses.{$status}", 'Verification Required');
    }

    public static function rejectionReasons(): array
    {
        return (array) config('guardian_relationships.rejection_reasons', []);
    }
}
