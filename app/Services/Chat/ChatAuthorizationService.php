<?php

namespace App\Services\Chat;

use App\Enums\EnrollmentStatus;
use App\Enums\ParentChildInvitationStatus;
use App\Models\Conversation;
use App\Models\MessageRequest;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\ParentChildInvitation;
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

            return $this->allow(! $hasEnrollment);
        }

        if ($initiatorIsInstructor && $targetIsLearner) {
            $hasEnrollment = $this->hasLearnerInstructorEnrollmentRelation($target->id, $initiator->id);

            if (! $hasEnrollment) {
                return $this->deny('no-enrollment-relation');
            }

            return $this->allow(false);
        }

        if ($initiatorIsLearner && $targetIsLearner) {
            if ($this->hasApprovedParentChildRelation($initiator->id, $target->id)) {
                return $this->allow(false);
            }
        }

        return $this->deny('unsupported-context-pair');
    }

    public function canSubscribeToConversation(User $user, Conversation $conversation): bool
    {
        if (! $this->canViewConversation($user, $conversation) || ! $this->participantsAreActive($conversation)) {
            return false;
        }

        if ((string) $conversation->conversation_type === Conversation::TYPE_GUARDIAN_INVITATION) {
            $invitation = $this->guardianInvitationForConversation($conversation);

            return $invitation !== null && $this->invitationAllowsLiveMessaging($invitation);
        }

        $relationship = $this->directParentChildRelationship($conversation);

        return $relationship === null
            || (! $relationship->trashed() && $relationship->isVerifiedActive());
    }

    public function canSendMessage(User $user, Conversation $conversation): bool
    {
        return $this->canSubscribeToConversation($user, $conversation)
            && in_array((string) $conversation->status, [
                Conversation::STATUS_ACTIVE,
                Conversation::STATUS_ACCEPTED,
            ], true);
    }

    public function canInitiateGuardianInvitationConversation(User $actor, ParentChildInvitation $invitation): bool
    {
        if (
            $actor->status !== User::STATUS_ACTIVE
            || (int) $actor->id !== (int) $invitation->child_user_id
        ) {
            return false;
        }

        return $this->invitationAllowsLiveMessaging($invitation);
    }

    public function invitationRelationshipAllowsMessaging(ParentChildInvitation $invitation): bool
    {
        $invitation->loadMissing('parentChildAccount');
        $relationship = $invitation->parentChildAccount;

        if (
            ! $relationship instanceof ParentChildAccount
            || $relationship->trashed()
            || (int) $relationship->parent_user_id !== (int) $invitation->inviter_parent_user_id
            || (int) $relationship->child_user_id !== (int) $invitation->child_user_id
        ) {
            return false;
        }

        if ($relationship->isVerifiedActive()) {
            return true;
        }

        return $relationship->relationship_status === ParentChildAccount::STATUS_PENDING
            && in_array($relationship->relationship_verified_status, [
                ParentChildAccount::VERIFICATION_PENDING,
                ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
            ], true);
    }

    public function invitationAllowsLiveMessaging(ParentChildInvitation $invitation): bool
    {
        if ($invitation->isExpired()) {
            return false;
        }

        if ($invitation->status === ParentChildInvitationStatus::Pending) {
            return true;
        }

        return $invitation->status === ParentChildInvitationStatus::Accepted
            && $this->invitationRelationshipAllowsMessaging($invitation);
    }

    public function canViewConversation(User $user, Conversation $conversation): bool
    {
        if ((string) User::query()->whereKey($user->id)->value('status') !== User::STATUS_ACTIVE) {
            return false;
        }

        if ((string) $conversation->conversation_type === Conversation::TYPE_GUARDIAN_INVITATION) {
            return $this->isParticipant($user, $conversation)
                && $this->guardianInvitationForConversation($conversation) !== null;
        }

        return $this->isParticipant($user, $conversation)
            || $this->isAdminSupportSharedConversation($user, $conversation);
    }

    public function canViewMessageRequest(User $user, MessageRequest $messageRequest): bool
    {
        return $user->id === $messageRequest->requester_id || $user->id === $messageRequest->instructor_id;
    }

    public function isParticipant(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->participant_one_id || $user->id === $conversation->participant_two_id;
    }

    private function participantsAreActive(Conversation $conversation): bool
    {
        $conversation->loadMissing(['participantOne:id,status', 'participantTwo:id,status']);

        return $conversation->participantOne?->status === User::STATUS_ACTIVE
            && $conversation->participantTwo?->status === User::STATUS_ACTIVE;
    }

    private function directParentChildRelationship(Conversation $conversation): ?ParentChildAccount
    {
        if ((string) $conversation->conversation_type !== Conversation::TYPE_DIRECT) {
            return null;
        }

        return ParentChildAccount::withTrashed()
            ->where(function ($query) use ($conversation): void {
                $query->where(function ($pairQuery) use ($conversation): void {
                    $pairQuery->where('parent_user_id', $conversation->participant_one_id)
                        ->where('child_user_id', $conversation->participant_two_id);
                })->orWhere(function ($pairQuery) use ($conversation): void {
                    $pairQuery->where('parent_user_id', $conversation->participant_two_id)
                        ->where('child_user_id', $conversation->participant_one_id);
                });
            })
            ->first();
    }

    private function guardianInvitationForConversation(Conversation $conversation): ?ParentChildInvitation
    {
        if ((string) $conversation->conversation_type !== Conversation::TYPE_GUARDIAN_INVITATION) {
            return null;
        }

        $conversation->loadMissing('parentChildInvitation');
        $invitation = $conversation->parentChildInvitation;

        if (! $invitation instanceof ParentChildInvitation) {
            return null;
        }

        return $conversation->pair_key === Conversation::makePairKey(
            (int) $invitation->inviter_parent_user_id,
            (int) $invitation->child_user_id,
        )
            ? $invitation
            : null;
    }

    protected function isAdminSupportSharedConversation(User $user, Conversation $conversation): bool
    {
        return (string) $conversation->conversation_type === Conversation::TYPE_ADMIN_SUPPORT
            && $this->isAdminContext($user);
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
        $hasDirectEnrollment = ModuleEnrollment::query()
            ->where('user_id', $learnerId)
            ->where('status', EnrollmentStatus::Approved)
            ->whereHas('module', function ($query) use ($instructorId) {
                $query->where('created_by', $instructorId);
            })
            ->exists();

        if ($hasDirectEnrollment) {
            return true;
        }

        $linkedChildIds = ParentChildAccount::query()
            ->accessEligible()
            ->where('parent_user_id', $learnerId)
            ->pluck('child_user_id');

        if ($linkedChildIds->isEmpty()) {
            return false;
        }

        return ModuleEnrollment::query()
            ->whereIn('user_id', $linkedChildIds)
            ->where('status', EnrollmentStatus::Approved)
            ->whereHas('module', function ($query) use ($instructorId) {
                $query->where('created_by', $instructorId);
            })
            ->exists();
    }

    protected function hasApprovedParentChildRelation(int $firstUserId, int $secondUserId): bool
    {
        return ParentChildAccount::query()
            ->accessEligible()
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where(function ($innerQuery) use ($firstUserId, $secondUserId) {
                    $innerQuery->where('parent_user_id', $firstUserId)
                        ->where('child_user_id', $secondUserId);
                })->orWhere(function ($innerQuery) use ($firstUserId, $secondUserId) {
                    $innerQuery->where('parent_user_id', $secondUserId)
                        ->where('child_user_id', $firstUserId);
                });
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
    ): bool {
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
            || $user->can('moderate chat')
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
                || $user->hasRole('parent')
                || $user->role === 'learner'
                || $user->role === 'parent'
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
