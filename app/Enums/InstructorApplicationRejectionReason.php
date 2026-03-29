<?php

namespace App\Enums;

enum InstructorApplicationRejectionReason: string
{
    case IncompleteInformation = 'incomplete_information';
    case InsufficientBackground = 'insufficient_background';
    case DoesNotMeetGuidelines = 'does_not_meet_guidelines';
    case InvalidCredentials = 'invalid_credentials';
    case ExpertiseNotAligned = 'expertise_not_aligned';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IncompleteInformation => 'Incomplete application information',
            self::InsufficientBackground => 'Insufficient educational or professional background',
            self::DoesNotMeetGuidelines => 'Application does not meet platform guidelines',
            self::InvalidCredentials => 'Invalid or unverifiable credentials',
            self::ExpertiseNotAligned => 'Content expertise not aligned with platform topics',
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
