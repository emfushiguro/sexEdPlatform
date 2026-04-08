<?php

namespace App\Support\Chat;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessagePayloadFormatter
{
    public function format(Message $message): array
    {
        $message->loadMissing([
            'sender:id,name,role,status,chat_status',
            'sender.learnerProfile:id,user_id,avatar_path',
            'sender.instructorProfile:id,user_id,profile_photo_path',
            'attachments:id,message_id,uploaded_by_id,disk,path,file_name,mime_type,size_bytes',
        ]);

        $sender = $message->sender;
        $avatarPath = $sender?->learnerProfile?->avatar_path
            ?? $sender?->instructorProfile?->profile_photo_path;

        $attachmentPayload = $message->deleted_at !== null
            ? []
            : $message->attachments
                ->map(fn (MessageAttachment $attachment) => $this->formatAttachment($attachment))
                ->values()
                ->all();

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender?->name,
            'sender_role' => $sender?->role,
            'sender_status' => $this->normalizeUserStatus($sender?->chat_status ?? $sender?->status),
            'sender_avatar_url' => $this->resolveAvatarUrl($avatarPath),
            'message_type' => $message->message_type,
            'message_body' => $message->message_body,
            'attachments' => $attachmentPayload,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'deleted_at' => $message->deleted_at?->toIso8601String(),
            'deleted_by_id' => $message->deleted_by_id,
            'is_deleted' => $message->deleted_at !== null,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    protected function formatAttachment(MessageAttachment $attachment): array
    {
        $url = Storage::disk($attachment->disk)->url($attachment->path);
        $mime = strtolower((string) $attachment->mime_type);
        $isVoiceNote = Str::startsWith(strtolower((string) $attachment->path), 'chat/voice_notes/')
            || Str::startsWith($mime, 'audio/');

        return [
            'id' => $attachment->id,
            'file_name' => $attachment->file_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => (int) $attachment->size_bytes,
            'url' => $url,
            'preview_url' => Str::startsWith($mime, ['image/', 'video/']) ? $url : null,
            'is_image' => Str::startsWith($mime, 'image/'),
            'is_video' => Str::startsWith($mime, 'video/'),
            'is_audio' => Str::startsWith($mime, 'audio/'),
            'is_voice_note' => $isVoiceNote,
        ];
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
