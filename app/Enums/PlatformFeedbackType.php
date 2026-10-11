<?php

namespace App\Enums;

enum PlatformFeedbackType: string
{
    case General = 'general';
    case BugReport = 'bug_report';
    case FeatureSuggestion = 'feature_suggestion';
    case AccessibilityIssue = 'accessibility_issue';
    case HelpContentIssue = 'help_content_issue';
    case AccountPaymentIssue = 'account_payment_issue';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Inquiry',
            self::BugReport => 'Report a Problem',
            self::FeatureSuggestion => 'Suggestion',
            self::AccessibilityIssue => 'Technical Issue',
            self::HelpContentIssue => 'Content Concern',
            self::AccountPaymentIssue => 'Account or Payment Issue',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
