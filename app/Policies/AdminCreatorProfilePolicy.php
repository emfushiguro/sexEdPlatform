<?php

namespace App\Policies;

use App\Models\AdminCreatorProfile;
use App\Models\User;

class AdminCreatorProfilePolicy
{
    public function update(User $user, AdminCreatorProfile $profile): bool
    {
        return (int) $user->id === (int) $profile->user_id
            && ($user->hasRole('admin') || (string) $user->role === 'admin');
    }
}
