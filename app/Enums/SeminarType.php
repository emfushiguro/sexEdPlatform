<?php

namespace App\Enums;

enum SeminarType: string
{
    case Seminar = 'seminar';
    case Webinar = 'webinar';
    case Physical = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::Seminar => 'Seminar',
            self::Webinar => 'Webinar',
            self::Physical => 'Physical',
        };
    }
}
