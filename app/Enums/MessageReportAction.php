<?php

namespace App\Enums;

enum MessageReportAction: string
{
    case DismissReport = 'dismiss_report';
    case Warning = 'warning';
    case ChatRestriction = 'chat_restriction';
    case TemporarySuspension = 'temporary_suspension';
    case EscalateToModerationCase = 'escalate_to_moderation_case';
    case OtherEnforcementAction = 'other_enforcement_action';

    public function label(): string
    {
        return match ($this) {
            self::DismissReport => 'Dismiss report',
            self::Warning => 'Warning',
            self::ChatRestriction => 'Chat restriction',
            self::TemporarySuspension => 'Temporary suspension',
            self::EscalateToModerationCase => 'Escalate to moderation case',
            self::OtherEnforcementAction => 'Other enforcement action',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $action): string => $action->value, self::cases());
    }
}
