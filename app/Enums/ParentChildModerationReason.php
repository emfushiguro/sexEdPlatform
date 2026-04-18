<?php

namespace App\Enums;

enum ParentChildModerationReason: string
{
    case InaccurateInformation = 'inaccurate_information';
    case GuidelineViolation = 'platform_guideline_violation';
    case Others = 'others';

    public static function values(): array
    {
        return array_map(static fn (self $reason): string => $reason->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::InaccurateInformation => 'Inaccurate or misleading information',
            self::GuidelineViolation => 'Violates platform guidelines',
            self::Others => 'Others',
        };
    }
}
