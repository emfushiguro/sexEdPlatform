<?php

namespace App\Listeners\Chat;

use App\Events\Chat\MessageSent;
use App\Models\User;
use App\Notifications\Chat\NewChatMessageNotification;

class SendInAppChatMessageNotification
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message->loadMissing('conversation');
        $conversation = $message->conversation;

        if (!$conversation) {
            return;
        }

        $senderId = (int) $message->sender_id;
        $participantOneId = (int) $conversation->participant_one_id;
        $participantTwoId = (int) $conversation->participant_two_id;

        $recipientId = $participantOneId === $senderId ? $participantTwoId : $participantOneId;

        if ($recipientId <= 0 || $recipientId === $senderId) {
            return;
        }

        $recipient = User::query()->find($recipientId);

        if (!$recipient) {
            return;
        }

        $recipient->notify(new NewChatMessageNotification($message, $conversation));
    }
}
