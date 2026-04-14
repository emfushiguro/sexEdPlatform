<?php

namespace App\Enums;

enum ContentReportAction: string
{
    case WarningInstructor = 'warning_instructor';
    case DirectMessageInstructor = 'direct_message_instructor';
    case RestrictInstructorAccount = 'restrict_instructor_account';
    case BanInstructor = 'ban_instructor';
    case TakeDownModule = 'take_down_module';
    case DismissReport = 'dismiss_report';

    public function label(): string
    {
        return match ($this) {
            self::WarningInstructor => 'Issue warning to instructor',
            self::DirectMessageInstructor => 'Direct message instructor',
            self::RestrictInstructorAccount => 'Restrict instructor account',
            self::BanInstructor => 'Ban instructor account',
            self::TakeDownModule => 'Take down reported module',
            self::DismissReport => 'Dismiss report',
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
