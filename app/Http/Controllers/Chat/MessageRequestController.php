<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageRequestResolved;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ResolveMessageRequestRequest;
use App\Models\Conversation;
use App\Models\MessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $requests = MessageRequest::query()
            ->when($user->role === 'instructor', function ($query) use ($user) {
                $query->where('instructor_id', $user->id)
                    ->where('status', MessageRequest::STATUS_PENDING);
            }, function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->where('status', MessageRequest::STATUS_PENDING);
            })
            ->with(['requester:id,name,role', 'instructor:id,name,role'])
            ->latest('id')
            ->get()
            ->map(function (MessageRequest $messageRequest) {
                return [
                    'id' => $messageRequest->id,
                    'requester_id' => $messageRequest->requester_id,
                    'instructor_id' => $messageRequest->instructor_id,
                    'status' => $messageRequest->status,
                    'initial_message' => $messageRequest->initial_message,
                    'accepted_conversation_id' => $messageRequest->accepted_conversation_id,
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

        $pairKey = Conversation::makePairKey((int) $messageRequest->requester_id, (int) $messageRequest->instructor_id);
        $contextKey = Conversation::makeContextKey(Conversation::TYPE_DIRECT, null);
        $orderedIds = [(int) $messageRequest->requester_id, (int) $messageRequest->instructor_id];
        sort($orderedIds, SORT_NUMERIC);

        $conversation = Conversation::query()->firstOrCreate(
            [
                'pair_key' => $pairKey,
                'context_key' => $contextKey,
            ],
            [
                'participant_one_id' => $orderedIds[0],
                'participant_two_id' => $orderedIds[1],
                'conversation_type' => Conversation::TYPE_DIRECT,
                'status' => Conversation::STATUS_ACTIVE,
            ]
        );

        $messageRequest->forceFill([
            'status' => MessageRequest::STATUS_ACCEPTED,
            'accepted_conversation_id' => $conversation->id,
            'decided_by_id' => $request->user()->id,
            'decided_at' => now(),
        ])->save();

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

        $messageRequest->forceFill([
            'status' => MessageRequest::STATUS_DECLINED,
            'decided_by_id' => $request->user()->id,
            'decided_at' => now(),
        ])->save();

        event(new MessageRequestResolved($messageRequest));

        return response()->json([
            'message_request' => $messageRequest->fresh(),
        ]);
    }
}
