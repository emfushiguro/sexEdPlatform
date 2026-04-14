<?php

namespace App\Services\Chat;

use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\MessageRequest;
use App\Models\ModuleEnrollment;
use App\Models\User;

class ChatAuthorizationService
{
    /**
     * @return array{allowed: bool, requires_request: bool, reason: ?string}
     */
    public function evaluateStart(User $initiator, User $target): array
    {
        if ($initiator->id === $target->id) {
            return $this->deny('self-chat-not-allowed');
        }

        $initiatorIsAdmin = $this->isAdminContext($initiator);
        $targetIsAdmin = $this->isAdminContext($target);
        $initiatorIsInstructor = $this->isInstructorContext($initiator);
        $targetIsInstructor = $this->isInstructorContext($target);
        $initiatorIsLearner = $this->isLearnerContext($initiator);
        $targetIsLearner = $this->isLearnerContext($target);

        if ($this->isAdminPair(
            $initiatorIsAdmin,
            $targetIsAdmin,
            $initiatorIsInstructor,
            $targetIsInstructor,
            $initiatorIsLearner,
            $targetIsLearner,
        )) {
            return $this->allow(false);
        }

        if ($initiatorIsLearner && $targetIsInstructor) {
            $hasEnrollment = $this->hasLearnerInstructorEnrollmentRelation($initiator->id, $target->id);

            return $this->allow(!$hasEnrollment);
        }

        if ($initiatorIsInstructor && $targetIsLearner) {
            $hasEnrollment = $this->hasLearnerInstructorEnrollmentRelation($target->id, $initiator->id);

            if (!$hasEnrollment) {
                return $this->deny('no-enrollment-relation');
            }

            return $this->allow(false);
        }

        return $this->deny('unsupported-context-pair');
    }

    public function canSubscribeToConversation(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function canSendMessage(User $user, Conversation $conversation): bool
    {
        if (!$this->isParticipant($user, $conversation)) {
            return false;
        }

        return in_array((string) $conversation->status, [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_ACCEPTED,
        ], true);
    }

    public function canViewMessageRequest(User $user, MessageRequest $messageRequest): bool
    {
        return $user->id === $messageRequest->requester_id || $user->id === $messageRequest->instructor_id;
    }

    public function isParticipant(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->participant_one_id || $user->id === $conversation->participant_two_id;
    }

    public function normalizePairKey(int $firstUserId, int $secondUserId): string
    {
        return Conversation::makePairKey($firstUserId, $secondUserId);
    }

    public function isValidConversationType(string $conversationType): bool
    {
        return Conversation::isSupportedConversationType($conversationType);
    }

    protected function hasLearnerInstructorEnrollmentRelation(int $learnerId, int $instructorId): bool
    {
        return ModuleEnrollment::query()
            ->where('user_id', $learnerId)
            ->where('status', EnrollmentStatus::Approved)
            ->whereHas('module', function ($query) use ($instructorId) {
                $query->where('created_by', $instructorId);
            })
            ->exists();
    }

    protected function isAdminPair(
        bool $firstIsAdmin,
        bool $secondIsAdmin,
        bool $firstIsInstructor,
        bool $secondIsInstructor,
        bool $firstIsLearner,
        bool $secondIsLearner,
    ): bool
    {
        if ($firstIsAdmin && ($secondIsInstructor || $secondIsLearner)) {
            return true;
        }

        if ($secondIsAdmin && ($firstIsInstructor || $firstIsLearner)) {
            return true;
        }

        return false;
    }

    protected function isAdminContext(User $user): bool
    {
        return $user->can('access admin panel')
            || $user->can('manage users')
            || $user->hasRole('admin')
            || $user->role === 'admin';
    }

    protected function isInstructorContext(User $user): bool
    {
        return ! $this->isAdminContext($user)
            && (
                $user->can('access instructor panel')
                || $user->can('view learners')
                || $user->hasRole('instructor')
                || $user->role === 'instructor'
            );
    }

    protected function isLearnerContext(User $user): bool
    {
        return ! $this->isAdminContext($user)
            && ! $this->isInstructorContext($user)
            && (
                $user->can('access learner platform')
                || $user->can('take quizzes')
                || $user->hasRole('learner')
                || $user->role === 'learner'
            );
    }

    /**
     * @return array{allowed: bool, requires_request: bool, reason: ?string}
     */
    protected function allow(bool $requiresRequest): array
    {
        return [
            'allowed' => true,
            'requires_request' => $requiresRequest,
            'reason' => null,
        ];
    }

    /**
     * @return array{allowed: bool, requires_request: bool, reason: ?string}
     */
    protected function deny(string $reason): array
    {
        return [
            'allowed' => false,
            'requires_request' => false,
            'reason' => $reason,
        ];
    }
}
