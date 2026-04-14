<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view quizzes');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->can('view quizzes')
            && ($this->ownsQuiz($user, $quiz) || $this->canManageAcrossOwnership($user));
    }

    public function create(User $user): bool
    {
        return $user->can('create quizzes');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->can('edit quizzes')
            && ($this->ownsQuiz($user, $quiz) || $this->canManageAcrossOwnership($user));
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->can('delete quizzes')
            && ($this->ownsQuiz($user, $quiz) || $this->canManageAcrossOwnership($user));
    }

    private function canManageAcrossOwnership(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('review modules') || $user->can('publish modules');
    }

    private function ownsQuiz(User $user, Quiz $quiz): bool
    {
        $moduleOwnerId = (int) ($quiz->module?->created_by ?? 0);

        if ($moduleOwnerId > 0) {
            return $moduleOwnerId === (int) $user->id;
        }

        return (int) ($quiz->lesson?->module?->created_by ?? 0) === (int) $user->id;
    }
}
