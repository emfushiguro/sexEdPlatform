<?php

namespace App\Enums;

enum ContentReportTargetType: string
{
    case Module = 'module';
    case Instructor = 'instructor';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
