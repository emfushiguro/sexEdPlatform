<?php

namespace App\Enums;

enum ModerationCaseStatus: string
{
    case Reported = 'reported';
    case Triaged = 'triaged';
    case Investigating = 'investigating';
    case ConfirmedViolation = 'confirmed_violation';
    case NoViolation = 'no_violation';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Triaged => 'Triaged',
            self::Investigating => 'Investigating',
            self::ConfirmedViolation => 'Confirmed Violation',
            self::NoViolation => 'No Violation',
            self::Resolved => 'Resolved',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
