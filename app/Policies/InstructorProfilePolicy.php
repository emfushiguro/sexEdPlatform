<?php

namespace App\Policies;

use App\Models\InstructorProfile;
use App\Models\User;

class InstructorProfilePolicy
{
    public function update(User $user, InstructorProfile $profile): bool
    {
        return (int) $user->id === (int) $profile->user_id && $user->hasRole('instructor');
    }
}
