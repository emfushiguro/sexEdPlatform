<?php

namespace App\Enums;

enum ModuleReviewRejectionReason: string
{
    case InaccurateEducationalInformation = 'inaccurate_educational_information';
    case InappropriateContent = 'inappropriate_content';
    case LowQualityLessons = 'low_quality_lessons';
    case MissingContent = 'missing_content';
    case QuizErrors = 'quiz_errors';
    case PoorModuleStructure = 'poor_module_structure';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InaccurateEducationalInformation => 'Inaccurate educational information',
            self::InappropriateContent => 'Inappropriate content',
            self::LowQualityLessons => 'Low-quality lessons',
            self::MissingContent => 'Missing content',
            self::QuizErrors => 'Quiz errors',
            self::PoorModuleStructure => 'Poor module structure',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $reason): string => $reason->value, self::cases());
    }
}
