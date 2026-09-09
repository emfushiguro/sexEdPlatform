<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\ParentChildInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class GuardianInvitationConversationService
{
    public function __construct(private readonly ChatAuthorizationService $authorization)
    {
    }

    public function createOrGet(\App\Models\User $actor, ParentChildInvitation $invitation): Conversation
    {
        return DB::transaction(function () use ($actor, $invitation): Conversation {
            $locked = ParentChildInvitation::query()
                ->with('conversation')
                ->lockForUpdate()
                ->findOrFail($invitation->id);

            $existing = $locked->conversation;

            if ($existing !== null) {
                $expectedPair = Conversation::makePairKey(
                    (int) $locked->inviter_parent_user_id,
                    (int) $locked->child_user_id,
                );

                if (
                    $existing->conversation_type !== Conversation::TYPE_GUARDIAN_INVITATION
                    || $existing->pair_key !== $expectedPair
                    || ! $this->authorization->isParticipant($actor, $existing)
                ) {
                    throw new AuthorizationException('You are not allowed to access this invitation conversation.');
                }

                return $existing;
            }

            if (! $this->authorization->canInitiateGuardianInvitationConversation($actor, $locked)) {
                throw new AuthorizationException('Only the invited learner can start this conversation.');
            }

            $participantIds = [
                (int) $locked->inviter_parent_user_id,
                (int) $locked->child_user_id,
            ];
            sort($participantIds, SORT_NUMERIC);

            return Conversation::query()->firstOrCreate(
                ['parent_child_invitation_id' => $locked->id],
                [
                    'participant_one_id' => $participantIds[0],
                    'participant_two_id' => $participantIds[1],
                    'pair_key' => Conversation::makePairKey($participantIds[0], $participantIds[1]),
                    'conversation_type' => Conversation::TYPE_GUARDIAN_INVITATION,
                    'status' => Conversation::STATUS_ACTIVE,
                    'context_key' => Conversation::makeContextKey(
                        Conversation::TYPE_GUARDIAN_INVITATION,
                        $locked->id,
                    ),
                ],
            );
        });
    }
}
