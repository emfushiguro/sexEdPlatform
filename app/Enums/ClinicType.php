<?php

namespace App\Enums;

enum ClinicType: string
{
    case CLINIC = 'Clinic';
    case TESTING_CENTER = 'Testing Center';
    case HOSPITAL = 'Hospital';
    case BARANGAY_HEALTH_STATION = 'Barangay Health Station';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_combine(self::values(), self::values());
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::CLINIC => 'General Clinic',
            self::TESTING_CENTER => 'HIV/STD Testing Center',
            self::HOSPITAL => 'Hospital',
            self::BARANGAY_HEALTH_STATION => 'Barangay Health Station',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::CLINIC => 'medical-clinic',
            self::TESTING_CENTER => 'test-tube',
            self::HOSPITAL => 'hospital',
            self::BARANGAY_HEALTH_STATION => 'community-health',
        };
    }
}