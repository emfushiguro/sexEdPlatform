<?php

namespace App\Enums;

enum InstructorRestrictionAction: string
{
    case WarningOnly = 'warning_only';
    case Restrict3Days = 'restrict_3_days';
    case Restrict14Days = 'restrict_14_days';
    case SuspensionReview = 'suspension_review';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $action): string => $action->value, self::cases());
    }
}
