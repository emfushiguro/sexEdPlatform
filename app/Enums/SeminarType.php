<?php

namespace App\Enums;

enum SeminarType: string
{
    case Webinar = 'webinar';
    case Physical = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::Webinar => 'Webinar',
            self::Physical => 'Physical',
        };
    }
}
