<?php

namespace App\Enums;

enum ContentReportReason: string
{
    case InappropriateContent = 'inappropriate_content';
    case MisleadingInformation = 'misleading_information';
    case PlagiarizedContent = 'plagiarized_content';
    case OffensiveLanguage = 'offensive_language';
    case HarmfulMaterial = 'harmful_material';
    case SpamOrPromotionalAbuse = 'spam_or_promotional_abuse';

    public function label(): string
    {
        return match ($this) {
            self::InappropriateContent => 'Inappropriate content',
            self::MisleadingInformation => 'Misleading information',
            self::PlagiarizedContent => 'Plagiarized content',
            self::OffensiveLanguage => 'Offensive language',
            self::HarmfulMaterial => 'Incorrect or harmful educational material',
            self::SpamOrPromotionalAbuse => 'Spam or promotional abuse',
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
