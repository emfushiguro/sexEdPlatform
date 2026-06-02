<?php

use App\Models\Conversation;
use App\Services\Chat\ChatAuthorizationService;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Seminars\SeminarAccessService;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('seminars.{seminarId}', function (User $user, int $seminarId) {
    $seminar = Seminar::query()->find($seminarId);

    return $seminar !== null
        && app(SeminarAccessService::class)->canViewLiveChannel($user, $seminar);
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, int $conversationId) {
    $conversation = Conversation::query()->find($conversationId);

    if ($conversation === null) {
        return false;
    }

    return app(ChatAuthorizationService::class)->canSubscribeToConversation($user, $conversation);
});

Broadcast::channel('chat.requests.user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});

Broadcast::channel('chat.presence', function ($user) {
    return [
        'id' => (int) $user->id,
        'name' => $user->name,
        'status' => $user->chat_status,
    ];
});
