<?php

namespace App\Enums;

enum ViolationSeverity: string
{
    case Minor = 'minor';
    case Moderate = 'moderate';
    case Major = 'major';
    case Critical = 'critical';

    public function points(): int
    {
        return match ($this) {
            self::Minor => 1,
            self::Moderate => 3,
            self::Major => 5,
            self::Critical => 8,
        };
    }

    public function expiryDays(): int
    {
        return match ($this) {
            self::Minor => 30,
            self::Moderate => 90,
            self::Major => 180,
            self::Critical => 365,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $severity): string => $severity->value, self::cases());
    }
}
