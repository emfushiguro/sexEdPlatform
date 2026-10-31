<?php

namespace App\Policies;

use App\Models\InstructorIdentityVerification;
use App\Models\User;

class InstructorIdentityVerificationPolicy
{
    public function view(User $user, InstructorIdentityVerification $case): bool
    {
        return $user->id === $case->user_id
            || ($user->isAdmin() && $user->can('review instructor identities'));
    }

    public function decide(User $user, InstructorIdentityVerification $case): bool
    {
        return $user->id !== $case->user_id
            && $user->isAdmin()
            && $user->can('review instructor identities');
    }
}
