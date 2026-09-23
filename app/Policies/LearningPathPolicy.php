<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningPath;
use App\Models\User;

class LearningPathPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view modules');
    }

    public function view(User $user, LearningPath $path): bool
    {
        return $user->can('view modules');
    }

    public function create(User $user): bool
    {
        return $user->can('create modules');
    }

    public function update(User $user, LearningPath $path): bool
    {
        return $user->can('edit modules');
    }

    public function archive(User $user, LearningPath $path): bool
    {
        return $user->can('edit modules');
    }

    public function publish(User $user, ?LearningPath $path = null): bool
    {
        return $user->can('publish modules');
    }
}
