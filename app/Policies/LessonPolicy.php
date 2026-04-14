<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view lessons');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->can('view lessons')
            && ($this->ownsLesson($user, $lesson) || $this->canManageAcrossOwnership($user));
    }

    public function create(User $user): bool
    {
        return $user->can('create lessons');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('edit lessons')
            && ($this->ownsLesson($user, $lesson) || $this->canManageAcrossOwnership($user));
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('delete lessons')
            && ($this->ownsLesson($user, $lesson) || $this->canManageAcrossOwnership($user));
    }

    private function canManageAcrossOwnership(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('review modules') || $user->can('publish modules');
    }

    private function ownsLesson(User $user, Lesson $lesson): bool
    {
        return (int) ($lesson->module?->created_by ?? 0) === (int) $user->id;
    }
}
