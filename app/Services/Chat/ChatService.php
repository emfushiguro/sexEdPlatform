<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
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
        ?int $lessonTopicId = null,
    ): Conversation {
        $this->assertConversationTypeAuthorization($initiator, $target, $conversationType);

        $decision = $this->chatAuthorizationService->evaluateStart($initiator, $target);

        if (!$decision['allowed']) {
            throw new AuthorizationException((string) ($decision['reason'] ?? 'Conversation start denied.'));
        }

        if ($decision['requires_request']) {
            throw new DomainException('Message request required before direct conversation creation.');
        }

        $resolved = $this->chatContextResolver->resolve($conversationType, $moduleId, $lessonId, $quizId, $lessonTopicId);

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
                    'lesson_topic_id' => $resolved['lesson_topic_id'],
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

    /**
     * @return array{conversation: Conversation, messageRequest: MessageRequest, created: bool}
     */
    public function createOrGetPendingRequestConversation(User $requester, User $instructor, string $initialMessage): array
    {
        $decision = $this->chatAuthorizationService->evaluateStart($requester, $instructor);

        if (!$decision['allowed']) {
            throw new AuthorizationException((string) ($decision['reason'] ?? 'Message request denied.'));
        }

        if (!$decision['requires_request']) {
            throw new DomainException('Message request is not required for this pair.');
        }

        $trimmedMessage = trim($initialMessage);

        if ($trimmedMessage === '') {
            throw new InvalidArgumentException('An initial message is required when requesting to chat with this instructor.');
        }

        $pairKey = $this->chatAuthorizationService->normalizePairKey($requester->id, $instructor->id);
        $contextKey = Conversation::makeContextKey(Conversation::TYPE_DIRECT, null);
        $orderedParticipantIds = [$requester->id, $instructor->id];
        sort($orderedParticipantIds, SORT_NUMERIC);

        return DB::transaction(function () use ($requester, $instructor, $trimmedMessage, $pairKey, $contextKey, $orderedParticipantIds) {
            $conversation = Conversation::query()
                ->where('pair_key', $pairKey)
                ->where('context_key', $contextKey)
                ->lockForUpdate()
                ->first();

            if ($conversation === null) {
                $conversation = Conversation::query()->create([
                    'participant_one_id' => $orderedParticipantIds[0],
                    'participant_two_id' => $orderedParticipantIds[1],
                    'pair_key' => $pairKey,
                    'conversation_type' => Conversation::TYPE_DIRECT,
                    'status' => Conversation::STATUS_PENDING_REQUEST,
                    'context_key' => $contextKey,
                ]);
            }

            if (in_array((string) $conversation->status, [Conversation::STATUS_ACCEPTED, Conversation::STATUS_ACTIVE], true)) {
                throw new DomainException('Message request is not required for this pair.');
            }

            if ((string) $conversation->status === Conversation::STATUS_DECLINED) {
                throw new DomainException('This instructor declined the conversation request.');
            }

            $existingPendingRequest = MessageRequest::query()
                ->where('requester_id', $requester->id)
                ->where('instructor_id', $instructor->id)
                ->where('status', MessageRequest::STATUS_PENDING)
                ->where(function ($query) use ($conversation) {
                    $query->where('accepted_conversation_id', $conversation->id)
                        ->orWhereNull('accepted_conversation_id');
                })
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingPendingRequest !== null) {
                if ((string) $conversation->status !== Conversation::STATUS_PENDING_REQUEST) {
                    $conversation->forceFill([
                        'status' => Conversation::STATUS_PENDING_REQUEST,
                    ])->save();
                }

                if ((int) ($existingPendingRequest->accepted_conversation_id ?? 0) !== (int) $conversation->id) {
                    $existingPendingRequest->forceFill([
                        'accepted_conversation_id' => $conversation->id,
                    ])->save();
                }

                return [
                    'conversation' => $conversation,
                    'messageRequest' => $existingPendingRequest,
                    'created' => false,
                ];
            }

            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $requester->id,
                'message_body' => $trimmedMessage,
                'message_type' => 'text',
            ]);

            $conversation->forceFill([
                'status' => Conversation::STATUS_PENDING_REQUEST,
                'last_message_at' => $message->created_at,
            ])->save();

            $messageRequest = MessageRequest::query()->create([
                'requester_id' => $requester->id,
                'instructor_id' => $instructor->id,
                'status' => MessageRequest::STATUS_PENDING,
                'initial_message' => $trimmedMessage,
                'accepted_conversation_id' => $conversation->id,
            ]);

            return [
                'conversation' => $conversation,
                'messageRequest' => $messageRequest,
                'created' => true,
            ];
        });
    }

    public function sendMessage(User $sender, Conversation $conversation, ?string $messageBody, array $attachments = []): Message
    {
        if (!$this->chatAuthorizationService->canSendMessage($sender, $conversation)) {
            throw new AuthorizationException('User is not allowed to send to this conversation.');
        }

        $trimmedBody = trim((string) $messageBody);
        $uploadedFiles = array_values(array_filter($attachments, fn ($attachment) => $attachment instanceof UploadedFile));

        if ($trimmedBody === '' && count($uploadedFiles) < 1) {
            throw new InvalidArgumentException('Message body or attachments are required.');
        }

        return DB::transaction(function () use ($sender, $conversation, $trimmedBody, $uploadedFiles) {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message_body' => $trimmedBody,
                'message_type' => $this->resolveMessageType($trimmedBody, $uploadedFiles),
            ]);

            foreach ($uploadedFiles as $uploadedFile) {
                $path = $uploadedFile->store($this->resolveAttachmentDirectory($uploadedFile), 'public');

                $message->attachments()->create([
                    'uploaded_by_id' => $sender->id,
                    'disk' => 'public',
                    'path' => $path,
                    'file_name' => $uploadedFile->getClientOriginalName() ?: $uploadedFile->hashName(),
                    'mime_type' => $uploadedFile->getClientMimeType(),
                    'size_bytes' => (int) $uploadedFile->getSize(),
                ]);
            }

            $conversation->forceFill([
                'last_message_at' => $message->created_at,
            ])->save();

            return $message->fresh(['attachments']);
        });
    }

    protected function resolveAttachmentDirectory(UploadedFile $uploadedFile): string
    {
        $mimeType = strtolower((string) $uploadedFile->getClientMimeType());

        if (str_starts_with($mimeType, 'audio/')) {
            return 'chat/voice_notes/'.now()->format('Y/m');
        }

        return 'chat/attachments/'.now()->format('Y/m');
    }

    protected function resolveMessageType(string $body, array $attachments): string
    {
        $hasBody = $body !== '';
        $hasAttachments = count($attachments) > 0;

        if ($hasBody && $hasAttachments) {
            return 'mixed';
        }

        if ($hasAttachments) {
            return 'attachment';
        }

        return 'text';
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

        $initiatorIsAdmin = $initiator->can('moderate chat')
            || $initiator->can('access admin panel')
            || $initiator->hasRole('admin')
            || $initiator->role === 'admin';
        $targetIsAdmin = $target->can('moderate chat')
            || $target->can('access admin panel')
            || $target->hasRole('admin')
            || $target->role === 'admin';

        if (!$initiatorIsAdmin && !$targetIsAdmin) {
            throw new AuthorizationException('Admin support chat requires an admin participant.');
        }
    }
}
