<?php

namespace App\Notifications\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $this->message->loadMissing([
            'sender:id,name',
            'sender.learnerProfile:id,user_id,avatar_path',
            'sender.instructorProfile:id,user_id,profile_photo_path',
        ]);

        $sender = $this->message->sender;
        $senderName = trim((string) ($sender?->name ?? 'User')) ?: 'User';

        $preview = trim((string) $this->message->message_body);

        if ($preview === '') {
            $preview = 'New message received.';
        }

        if (mb_strlen($preview) > 140) {
            $preview = mb_substr($preview, 0, 137) . '...';
        }

        $senderAvatarUrl = $this->resolveSenderAvatarUrl($senderName);

        return [
            'type' => 'chat_message_received',
            'status' => 'unread',
            'title' => $senderName . ' sent you a message',
            'message' => '"' . $preview . '"',
            'message_preview' => $preview,
            'conversation_id' => $this->conversation->id,
            'chat_message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $senderName,
            'sender_avatar_url' => $senderAvatarUrl,
            'action_url' => route('chat.conversation.open', $this->conversation),
            'severity' => 'info',
        ];
    }

    private function resolveSenderAvatarUrl(string $senderName): string
    {
        $avatarPath = $this->message->sender?->learnerProfile?->avatar_path
            ?? $this->message->sender?->instructorProfile?->profile_photo_path;

        if (is_string($avatarPath) && trim($avatarPath) !== '') {
            $normalized = ltrim(trim($avatarPath), '/');

            if (Str::startsWith($normalized, ['http://', 'https://', '//'])) {
                return $normalized;
            }

            if (Str::startsWith($normalized, 'storage/')) {
                $normalized = substr($normalized, 8);
            }

            if (!str_contains($normalized, '/')) {
                $normalized = 'avatars/' . $normalized;
            }

            return Storage::url($normalized);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($senderName) . '&color=1D4ED8&background=EFF6FF';
    }
}
