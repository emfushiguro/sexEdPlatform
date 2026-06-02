<?php

namespace App\Support;

class ConnectorOptions
{
    public const CAVITE_PROVINCE_CODE = '402100000';
    public const CAVITE_CITY_CODE_PREFIX = '4021';

    public static function categories(): array
    {
        $configured = config('connector_permissions.categories', []);

        if (is_array($configured) && $configured !== []) {
            return $configured;
        }

        return [
            'government' => 'Government',
            'ngo' => 'NGO',
            'community_based_organization' => 'Community-based Organization',
            'school_educational_institution' => 'School / Educational Institution',
            'health_organization' => 'Health Organization',
            'advocacy_group' => 'Advocacy Group',
            'other' => 'Other',
        ];
    }

    public static function categoryKeys(): array
    {
        return array_keys(self::categories());
    }
}
