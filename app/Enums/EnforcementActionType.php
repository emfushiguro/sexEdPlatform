<?php

namespace App\Enums;

enum EnforcementActionType: string
{
    case Warning = 'warning';
    case ChatRestriction = 'chat_restriction';
    case ModulePublishRestriction = 'module_publish_restriction';
    case TemporarySuspension = 'temporary_suspension';
    case ExtendedSuspension = 'extended_suspension';
    case PermanentSuspension = 'permanent_suspension';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
