<?php

namespace App\Enums;

enum ParentChildModerationReason: string
{
    case ChildAppropriateStandards = 'child_appropriate_standards';
    case InaccurateInformation = 'inaccurate_information';
    case IncompleteContent = 'incomplete_content';
    case PoorStructure = 'poor_content_structure';
    case GuidelineViolation = 'platform_guideline_violation';
    case Others = 'others';

    public static function values(): array
    {
        return array_map(static fn (self $reason): string => $reason->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ChildAppropriateStandards => 'Does not match child-appropriate educational standards',
            self::InaccurateInformation => 'Inaccurate or misleading information',
            self::IncompleteContent => 'Incomplete module content',
            self::PoorStructure => 'Poor content structure',
            self::GuidelineViolation => 'Violates platform guidelines',
            self::Others => 'Others',
        };
    }
}
