<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\Conversation;
use App\Services\Chat\ChatAuthorizationService;
use App\Services\Chat\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected ChatAuthorizationService $chatAuthorizationService,
    ) {
    }

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        try {
            $message = $this->chatService->sendMessage(
                sender: $request->user(),
                conversation: $conversation,
                messageBody: (string) $request->validated('message_body'),
            );
        } catch (AuthorizationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        }

        event(new MessageSent($message));

        return response()->json([
            'message' => $message,
        ], 201);
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$this->chatAuthorizationService->canSubscribeToConversation($request->user(), $conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,role')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender?->name,
                    'message_body' => $message->message_body,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function since(Request $request, Conversation $conversation, int $lastMessageId): JsonResponse
    {
        if (!$this->chatAuthorizationService->canSubscribeToConversation($request->user(), $conversation)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $messages = $conversation->messages()
            ->where('id', '>', $lastMessageId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }
}
