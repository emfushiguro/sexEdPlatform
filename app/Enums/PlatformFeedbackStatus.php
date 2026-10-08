<?php

namespace App\Enums;

enum PlatformFeedbackStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewed => 'In Review',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Withdrawn => 'Withdrawn',
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
