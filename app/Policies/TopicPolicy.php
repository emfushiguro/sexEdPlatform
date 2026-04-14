<?php

namespace App\Policies;

use App\Models\LessonTopic;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TopicPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view lessons');
    }

    public function view(User $user, LessonTopic $topic): bool
    {
        return $user->can('view lessons')
            && ($this->ownsTopic($user, $topic) || $this->canManageAcrossOwnership($user));
    }

    public function create(User $user): bool
    {
        return $user->can('create lesson topics');
    }

    public function update(User $user, LessonTopic $topic): bool
    {
        return $user->can('edit lesson topics')
            && ($this->ownsTopic($user, $topic) || $this->canManageAcrossOwnership($user));
    }

    public function delete(User $user, LessonTopic $topic): bool
    {
        return $user->can('delete lesson topics')
            && ($this->ownsTopic($user, $topic) || $this->canManageAcrossOwnership($user));
    }

    private function canManageAcrossOwnership(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('review modules') || $user->can('publish modules');
    }

    private function ownsTopic(User $user, LessonTopic $topic): bool
    {
        return (int) ($topic->lesson?->module?->created_by ?? 0) === (int) $user->id;
    }
}
