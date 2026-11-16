<?php

namespace App\Notifications\Moderation;

use App\Models\UserSuspension;
use App\Support\NotificationPayloadNormalizer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspensionDurationChangedNotification extends Notification
{
    public function __construct(public readonly UserSuspension $suspension) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $payload = ['type' => 'moderation_suspension_duration_changed', 'user_suspension_id' => $this->suspension->id,
            'user_id' => $this->suspension->user_id, 'starts_at' => $this->suspension->starts_at?->toDateTimeString(),
            'ends_at' => $this->suspension->ends_at?->toDateTimeString()];

        return [...$payload, ...app(NotificationPayloadNormalizer::class)->normalize($payload)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->toArray($notifiable);

        return (new MailMessage)->subject($payload['title'])->line($payload['message'])
            ->action('View suspension status', route('moderation.suspension-status'));
    }
}
