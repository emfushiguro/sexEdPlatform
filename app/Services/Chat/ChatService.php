<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChatService
{
    public function __construct(
        protected ChatAuthorizationService $chatAuthorizationService,
        protected ChatContextResolver $chatContextResolver,
    ) {
    }

    public function createOrGetConversation(
        User $initiator,
        User $target,
        string $conversationType,
        ?int $moduleId = null,
        ?int $lessonId = null,
        ?int $quizId = null,
    ): Conversation {
        $this->assertConversationTypeAuthorization($initiator, $target, $conversationType);

        $decision = $this->chatAuthorizationService->evaluateStart($initiator, $target);

        if (!$decision['allowed']) {
            throw new AuthorizationException((string) ($decision['reason'] ?? 'Conversation start denied.'));
        }

        if ($decision['requires_request']) {
            throw new DomainException('Message request required before direct conversation creation.');
        }

        $resolved = $this->chatContextResolver->resolve($conversationType, $moduleId, $lessonId, $quizId);

        $pairKey = $this->chatAuthorizationService->normalizePairKey($initiator->id, $target->id);
        $orderedParticipantIds = [$initiator->id, $target->id];
        sort($orderedParticipantIds, SORT_NUMERIC);

        return DB::transaction(function () use ($pairKey, $conversationType, $resolved, $orderedParticipantIds) {
            return Conversation::query()->firstOrCreate(
                [
                    'pair_key' => $pairKey,
                    'context_key' => $resolved['context_key'],
                ],
                [
                    'participant_one_id' => $orderedParticipantIds[0],
                    'participant_two_id' => $orderedParticipantIds[1],
                    'conversation_type' => $conversationType,
                    'status' => Conversation::STATUS_ACTIVE,
                    'module_id' => $resolved['module_id'],
                    'lesson_id' => $resolved['lesson_id'],
                    'quiz_id' => $resolved['quiz_id'],
                ]
            );
        });
    }

    public function createMessageRequest(User $requester, User $instructor, string $initialMessage): MessageRequest
    {
        $decision = $this->chatAuthorizationService->evaluateStart($requester, $instructor);

        if (!$decision['allowed']) {
            throw new AuthorizationException((string) ($decision['reason'] ?? 'Message request denied.'));
        }

        if (!$decision['requires_request']) {
            throw new DomainException('Message request is not required for this pair.');
        }

        return MessageRequest::query()->create([
            'requester_id' => $requester->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => trim($initialMessage),
        ]);
    }

    public function sendMessage(User $sender, Conversation $conversation, string $messageBody): Message
    {
        if (!$this->chatAuthorizationService->canSendMessage($sender, $conversation)) {
            throw new AuthorizationException('User is not allowed to send to this conversation.');
        }

        return DB::transaction(function () use ($sender, $conversation, $messageBody) {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message_body' => trim($messageBody),
            ]);

            $conversation->forceFill([
                'last_message_at' => $message->created_at,
            ])->save();

            return $message;
        });
    }

    public function markConversationRead(User $user, Conversation $conversation, ?Message $message = null): ConversationRead
    {
        if (!$this->chatAuthorizationService->canSubscribeToConversation($user, $conversation)) {
            throw new AuthorizationException('User is not allowed to read this conversation.');
        }

        if ($message !== null && $message->conversation_id !== $conversation->id) {
            throw new InvalidArgumentException('Read message must belong to the target conversation.');
        }

        $lastMessageId = $message?->id ?? $conversation->messages()->latest('id')->value('id');

        return ConversationRead::query()->updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => $lastMessageId,
                'last_read_at' => now(),
            ]
        );
    }

    public function unreadCountForConversation(User $user, Conversation $conversation): int
    {
        $lastReadMessageId = ConversationRead::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->value('last_read_message_id');

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->when($lastReadMessageId !== null, function ($query) use ($lastReadMessageId) {
                $query->where('id', '>', $lastReadMessageId);
            })
            ->count();
    }

    protected function assertConversationTypeAuthorization(User $initiator, User $target, string $conversationType): void
    {
        if ($conversationType !== Conversation::TYPE_ADMIN_SUPPORT) {
            return;
        }

        $initiatorIsAdmin = $initiator->role === 'admin';
        $targetIsAdmin = $target->role === 'admin';

        if (!$initiatorIsAdmin && !$targetIsAdmin) {
            throw new AuthorizationException('Admin support chat requires an admin participant.');
        }
    }
}
