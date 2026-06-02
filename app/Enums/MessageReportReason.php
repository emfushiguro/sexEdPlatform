<?php

namespace App\Enums;

enum MessageReportReason: string
{
    case HarassmentAbuse = 'harassment_abuse';
    case InappropriateSexualContent = 'inappropriate_sexual_content';
    case SpamRepeatedMessages = 'spam_repeated_messages';
    case ThreateningBehavior = 'threatening_behavior';
    case Misinformation = 'misinformation';
    case Impersonation = 'impersonation';
    case OffensiveLanguage = 'offensive_language';
    case ChildSafetyConcern = 'child_safety_concern';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HarassmentAbuse => 'Harassment / Abuse',
            self::InappropriateSexualContent => 'Inappropriate Sexual Content',
            self::SpamRepeatedMessages => 'Spam / Repeated Messages',
            self::ThreateningBehavior => 'Threatening Behavior',
            self::Misinformation => 'Misinformation',
            self::Impersonation => 'Impersonation',
            self::OffensiveLanguage => 'Offensive Language',
            self::ChildSafetyConcern => 'Child Safety Concern',
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
