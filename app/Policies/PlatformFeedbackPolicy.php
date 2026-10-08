<?php

namespace App\Policies;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\User;

class PlatformFeedbackPolicy
{
    public function view(User $user, PlatformFeedback $feedback): bool
    {
        return $user->id === $feedback->user_id || $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, PlatformFeedback $feedback): bool
    {
        return $user->hasRole('admin');
    }

    public function withdraw(User $user, PlatformFeedback $feedback): bool
    {
        return $user->id === $feedback->user_id
            && $feedback->status === PlatformFeedbackStatus::New;
    }
}
