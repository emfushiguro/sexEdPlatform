<?php

namespace App\Services\Support;

use App\Models\User;

class TestimonialEligibility
{
    public function canSubmit(User $user): bool
    {
        return in_array((string) ($user->role ?? ''), ['learner', 'instructor'], true)
            && (string) ($user->account_type ?? '') !== 'parent'
            && $this->isAdult($user)
            && ($user->status === null || $user->status === User::STATUS_ACTIVE);
    }

    public function isAdult(User $user): bool
    {
        if ($user->role === 'instructor') {
            return true;
        }

        if (in_array($user->account_type, [User::ACCOUNT_TYPE_LEARNER_CHILD, User::ACCOUNT_TYPE_LEARNER_TEEN], true)) {
            return false;
        }

        $age = $user->calculateAge() ?? $user->age;

        return $age !== null && (int) $age >= 18;
    }
}
