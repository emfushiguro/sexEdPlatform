<?php

namespace App\Notifications\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Message $message,
        private readonly Conversation $conversation,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $preview = trim((string) $this->message->message_body);

        if ($preview === '') {
            $preview = 'New message received.';
        }

        if (mb_strlen($preview) > 140) {
            $preview = mb_substr($preview, 0, 137) . '...';
        }

        return [
            'type' => 'chat_message_received',
            'status' => 'unread',
            'title' => 'New Chat Message',
            'message' => $preview,
            'conversation_id' => $this->conversation->id,
            'chat_message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'action_url' => route('chat.conversation.open', $this->conversation),
            'severity' => 'info',
        ];
    }
}
