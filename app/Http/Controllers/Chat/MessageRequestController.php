<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageRequestResolved;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ResolveMessageRequestRequest;
use App\Models\Conversation;
use App\Models\MessageRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('manage message requests')) {
            return response()->json([
                'requests' => [],
            ]);
        }

        $requests = MessageRequest::query()
            ->where('instructor_id', $user->id)
            ->where('status', MessageRequest::STATUS_PENDING)
            ->with([
                'requester:id,name,role,status,chat_status',
                'requester.learnerProfile:id,user_id,avatar_path',
                'requester.instructorProfile:id,user_id,profile_photo_path',
                'instructor:id,name,role',
                'acceptedConversation:id,conversation_type,module_id,lesson_id,lesson_topic_id,quiz_id',
                'acceptedConversation.module:id,title',
                'acceptedConversation.lesson:id,title',
                'acceptedConversation.lessonTopic:id,title,lesson_id',
                'acceptedConversation.quiz:id,title',
            ])
            ->latest('id')
            ->get()
            ->map(function (MessageRequest $messageRequest) {
                $requester = $messageRequest->requester;
                $requesterAvatarPath = $requester?->learnerProfile?->avatar_path
                    ?? $requester?->instructorProfile?->profile_photo_path;

                return [
                    'id' => $messageRequest->id,
                    'requester_id' => $messageRequest->requester_id,
                    'instructor_id' => $messageRequest->instructor_id,
                    'status' => $messageRequest->status,
                    'initial_message' => $messageRequest->initial_message,
                    'accepted_conversation_id' => $messageRequest->accepted_conversation_id,
                    'context_label' => $this->buildContextLabel($messageRequest->acceptedConversation),
                    'requester_avatar_url' => $this->resolveAvatarUrl($requesterAvatarPath),
                    'requester_status' => $this->normalizeUserStatus($requester?->chat_status ?? $requester?->status),
                    'created_at' => $messageRequest->created_at?->toIso8601String(),
                    'requester' => $messageRequest->requester,
                    'instructor' => $messageRequest->instructor,
                ];
            })
            ->values();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    public function accept(ResolveMessageRequestRequest $request, MessageRequest $messageRequest): JsonResponse
    {
        if ((int) $request->user()->id !== (int) $messageRequest->instructor_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $conversation = null;

        try {
            DB::transaction(function () use ($request, $messageRequest, &$conversation) {
                $lockedRequest = MessageRequest::query()
                    ->whereKey($messageRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status === MessageRequest::STATUS_ACCEPTED) {
                    throw new DomainException('This request has already been accepted.');
                }

                if ($lockedRequest->status === MessageRequest::STATUS_DECLINED) {
                    throw new DomainException('This request has already been declined.');
                }

                $pairKey = Conversation::makePairKey((int) $lockedRequest->requester_id, (int) $lockedRequest->instructor_id);
                $contextKey = Conversation::makeContextKey(Conversation::TYPE_DIRECT, null);
                $orderedIds = [(int) $lockedRequest->requester_id, (int) $lockedRequest->instructor_id];
                sort($orderedIds, SORT_NUMERIC);

                $conversation = Conversation::query()
                    ->where('pair_key', $pairKey)
                    ->where('context_key', $contextKey)
                    ->lockForUpdate()
                    ->first();

                if ($conversation === null) {
                    $conversation = Conversation::query()->create([
                        'participant_one_id' => $orderedIds[0],
                        'participant_two_id' => $orderedIds[1],
                        'pair_key' => $pairKey,
                        'conversation_type' => Conversation::TYPE_DIRECT,
                        'status' => Conversation::STATUS_PENDING_REQUEST,
                        'context_key' => $contextKey,
                    ]);
                }

                if ((string) $conversation->status !== Conversation::STATUS_PENDING_REQUEST) {
                    throw new DomainException('This request has already been processed.');
                }

                $conversation->forceFill([
                    'status' => Conversation::STATUS_ACCEPTED,
                ])->save();

                $lockedRequest->forceFill([
                    'status' => MessageRequest::STATUS_ACCEPTED,
                    'accepted_conversation_id' => $conversation->id,
                    'decided_by_id' => $request->user()->id,
                    'decided_at' => now(),
                ])->save();

                $messageRequest->setRawAttributes($lockedRequest->getAttributes(), true);
                $messageRequest->syncOriginal();
            });
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        event(new MessageRequestResolved($messageRequest));

        return response()->json([
            'message_request' => $messageRequest->fresh(),
            'conversation' => $conversation,
        ]);
    }

    public function decline(ResolveMessageRequestRequest $request, MessageRequest $messageRequest): JsonResponse
    {
        if ((int) $request->user()->id !== (int) $messageRequest->instructor_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            DB::transaction(function () use ($request, $messageRequest) {
                $lockedRequest = MessageRequest::query()
                    ->whereKey($messageRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedRequest->status === MessageRequest::STATUS_ACCEPTED) {
                    throw new DomainException('This request has already been accepted.');
                }

                if ($lockedRequest->status === MessageRequest::STATUS_DECLINED) {
                    throw new DomainException('This request has already been declined.');
                }

                $conversation = null;

                if ($lockedRequest->accepted_conversation_id) {
                    $conversation = Conversation::query()
                        ->whereKey($lockedRequest->accepted_conversation_id)
                        ->lockForUpdate()
                        ->first();
                }

                if ($conversation === null) {
                    $pairKey = Conversation::makePairKey((int) $lockedRequest->requester_id, (int) $lockedRequest->instructor_id);
                    $contextKey = Conversation::makeContextKey(Conversation::TYPE_DIRECT, null);

                    $conversation = Conversation::query()
                        ->where('pair_key', $pairKey)
                        ->where('context_key', $contextKey)
                        ->lockForUpdate()
                        ->first();
                }

                if ($conversation !== null) {
                    if ((string) $conversation->status !== Conversation::STATUS_PENDING_REQUEST) {
                        throw new DomainException('This request has already been processed.');
                    }

                    $conversation->forceFill([
                        'status' => Conversation::STATUS_DECLINED,
                    ])->save();
                }

                $lockedRequest->forceFill([
                    'status' => MessageRequest::STATUS_DECLINED,
                    'accepted_conversation_id' => $conversation?->id,
                    'decided_by_id' => $request->user()->id,
                    'decided_at' => now(),
                ])->save();

                $messageRequest->setRawAttributes($lockedRequest->getAttributes(), true);
                $messageRequest->syncOriginal();
            });
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        event(new MessageRequestResolved($messageRequest));

        return response()->json([
            'message_request' => $messageRequest->fresh(),
        ]);
    }

    protected function buildContextLabel(?Conversation $conversation): string
    {
        if ($conversation === null) {
            return 'Direct Conversation';
        }

        return match ($conversation->conversation_type) {
            Conversation::TYPE_MODULE_CHAT => 'Module Discussion - '.($conversation->module?->title ?? 'Module'),
            Conversation::TYPE_LESSON_CHAT => 'Lesson Discussion - '.($conversation->lesson?->title ?? 'Lesson'),
            Conversation::TYPE_LESSON_TOPIC_CHAT => 'Lesson Topic Discussion - '.($conversation->lessonTopic?->title ?? 'Topic'),
            Conversation::TYPE_QUIZ_HELP => 'Quiz Help - '.($conversation->quiz?->title ?? 'Quiz'),
            Conversation::TYPE_ADMIN_SUPPORT => 'Platform Support',
            default => 'Direct Conversation',
        };
    }

    protected function normalizeUserStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        if ($normalized === 'active' || $normalized === 'online') {
            return 'online';
        }

        if ($normalized === 'inactive' || $normalized === 'do_not_disturb' || $normalized === 'dnd') {
            return 'do_not_disturb';
        }

        if (in_array($normalized, ['busy', 'offline'], true)) {
            return $normalized;
        }

        return 'offline';
    }

    protected function resolveAvatarUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $raw = (string) $path;

        if (Str::startsWith($raw, ['http://', 'https://', '//'])) {
            return $raw;
        }

        $normalized = ltrim($raw, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        if (!str_contains($normalized, '/')) {
            $normalized = 'avatars/'.$normalized;
        }

        if (!Storage::disk('public')->exists($normalized)) {
            return null;
        }

        return Storage::url($normalized);
    }
}
