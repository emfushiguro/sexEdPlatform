<?php

use App\Models\Conversation;
use App\Services\Chat\ChatAuthorizationService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
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
