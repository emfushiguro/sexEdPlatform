<?php

namespace App\Support;

use App\Models\InstructorModerationProfile;
use App\Models\User;

class InstructorRestrictionGate
{
    public function isRestricted(User $user): bool
    {
        $profile = $this->activeRestrictionProfile($user);

        return $profile !== null;
    }

    public function restrictionMessage(User $user): ?string
    {
        $profile = $this->activeRestrictionProfile($user);

        if (!$profile) {
            return null;
        }

        $endText = $profile->restriction_ends_at
            ? $profile->restriction_ends_at->toDayDateTimeString()
            : 'until further notice';

        return 'Module creation and review submission are temporarily restricted for your account ' . $endText . '.';
    }

    public function activeRestrictionProfile(User $user): ?InstructorModerationProfile
    {
        $profile = $user->relationLoaded('moderationProfile')
            ? $user->moderationProfile
            : $user->moderationProfile()->first();

        if (!$profile) {
            return null;
        }

        if ($profile->current_restriction_status !== 'restricted') {
            return null;
        }

        if ($profile->restriction_ends_at && $profile->restriction_ends_at->isPast()) {
            return null;
        }

        return $profile;
    }
}
