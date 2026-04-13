<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageSent;
use App\Events\Chat\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Requests\Chat\UpdateMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReport;
use App\Services\Chat\ChatAuthorizationService;
use App\Services\Chat\ChatService;
use App\Support\Chat\MessagePayloadFormatter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected ChatAuthorizationService $chatAuthorizationService,
        protected MessagePayloadFormatter $messagePayloadFormatter,
    ) {
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $attachments = $request->file('attachments', []);

        try {
            $message = $this->chatService->sendMessage(
                sender: $request->user(),
                conversation: $conversation,
                messageBody: (string) ($request->validated('message_body') ?? ''),
                attachments: is_array($attachments) ? $attachments : [$attachments],
            );

            $message->loadMissing([
                'sender:id,name,role,status,chat_status',
                'sender.learnerProfile:id,user_id,avatar_path',
                'sender.instructorProfile:id,user_id,profile_photo_path',
                'attachments:id,message_id,uploaded_by_id,disk,path,file_name,mime_type,size_bytes',
            ]);
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        event(new MessageSent($message));

        return response()->json([
            'message' => $this->messagePayloadFormatter->format($message),
        ], 201);
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$this->chatAuthorizationService->canSubscribeToConversation($request->user(), $conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $limit = min(max((int) $request->integer('limit', 30), 1), 50);
        $beforeMessageId = (int) $request->integer('before_message_id', 0);

        $query = $conversation->messages()
            ->with([
                'sender:id,name,role,status,chat_status',
                'sender.learnerProfile:id,user_id,avatar_path',
                'sender.instructorProfile:id,user_id,profile_photo_path',
                'attachments:id,message_id,uploaded_by_id,disk,path,file_name,mime_type,size_bytes',
            ])
            ->orderByDesc('id')
            ->when($beforeMessageId > 0, function ($messageQuery) use ($beforeMessageId) {
                $messageQuery->where('id', '<', $beforeMessageId);
            });

        $window = $query
            ->limit($limit + 1)
            ->get();

        $hasMoreBefore = $window->count() > $limit;

        $messages = $window
            ->take($limit)
            ->reverse()
            ->values()
            ->map(fn (Message $message) => $this->messagePayloadFormatter->format($message))
            ->values();

        return response()->json([
            'messages' => $messages,
            'meta' => [
                'limit' => $limit,
                'has_more_before' => $hasMoreBefore,
                'oldest_message_id' => $messages->first()['id'] ?? null,
            ],
        ]);
    }

    public function since(Request $request, Conversation $conversation, int $lastMessageId): JsonResponse
    {
        if (!$this->chatAuthorizationService->canSubscribeToConversation($request->user(), $conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $messages = $conversation->messages()
            ->with([
                'sender:id,name,role,status,chat_status',
                'sender.learnerProfile:id,user_id,avatar_path',
                'sender.instructorProfile:id,user_id,profile_photo_path',
                'attachments:id,message_id,uploaded_by_id,disk,path,file_name,mime_type,size_bytes',
            ])
            ->where('id', '>', $lastMessageId)
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->messagePayloadFormatter->format($message))
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        $user = $request->user();

        if (!$this->chatAuthorizationService->canSubscribeToConversation($user, $message->conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!$this->canMutateMessage($user, $message)) {
            return response()->json([
                'message' => 'Message edit window has expired.',
            ], 403);
        }

        $message->forceFill([
            'message_body' => trim((string) $request->validated('message_body')),
            'edited_at' => now(),
        ])->save();

        $message = $message->fresh(['sender', 'sender.learnerProfile', 'sender.instructorProfile', 'attachments']);

        event(new MessageUpdated($message, 'edited'));

        return response()->json([
            'message' => $this->messagePayloadFormatter->format($message),
        ]);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        if (!$this->chatAuthorizationService->canSubscribeToConversation($user, $message->conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($message->deleted_at !== null) {
            return response()->json([
                'message' => $this->messagePayloadFormatter->format($message->loadMissing(['sender', 'attachments'])),
            ]);
        }

        if (!$this->canMutateMessage($user, $message)) {
            return response()->json([
                'message' => 'Message delete window has expired.',
            ], 403);
        }

        $message->forceFill([
            'message_body' => '[message removed]',
            'message_type' => 'deleted',
            'edited_at' => now(),
            'deleted_at' => now(),
            'deleted_by_id' => $user->id,
        ])->save();

        $message = $message->fresh(['sender', 'sender.learnerProfile', 'sender.instructorProfile', 'attachments']);

        event(new MessageUpdated($message, 'deleted'));

        return response()->json([
            'message' => $this->messagePayloadFormatter->format($message),
        ]);
    }

    public function report(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        if (!$this->chatAuthorizationService->canSubscribeToConversation($user, $message->conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($message->deleted_at !== null) {
            return response()->json([
                'message' => 'Cannot report a removed message.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        MessageReport::query()->updateOrCreate(
            [
                'message_id' => $message->id,
                'reporter_id' => $user->id,
            ],
            [
                'conversation_id' => $message->conversation_id,
                'reason' => trim((string) ($validated['reason'] ?? '')) ?: null,
                'status' => 'open',
            ]
        );

        return response()->json([
            'reported' => true,
            'message' => 'Message reported for review.',
        ]);
    }

    protected function canMutateMessage($user, Message $message): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ((int) $message->sender_id !== (int) $user->id) {
            return false;
        }

        $windowMinutes = (int) config('chat.message_mutation_window_minutes', 15);

        if ($message->created_at === null) {
            return false;
        }

        return now()->lte($message->created_at->copy()->addMinutes($windowMinutes));
    }
}
