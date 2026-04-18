<?php

namespace App\Notifications\Moderation;

use App\Models\UserSuspension;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspensionIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly UserSuspension $suspension)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Suspension Notice')
            ->line('Your account currently has an active suspension.')
            ->line('Started at: ' . (optional($this->suspension->starts_at)?->format('M d, Y h:i A') ?? 'N/A'))
            ->line('Ends at: ' . (optional($this->suspension->ends_at)?->format('M d, Y h:i A') ?? 'Permanent'))
            ->line('You may submit an appeal from your suspension status page if eligible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'moderation_suspension_issued',
            'user_suspension_id' => $this->suspension->id,
            'user_id' => $this->suspension->user_id,
            'moderation_case_id' => $this->suspension->moderation_case_id,
            'enforcement_action_id' => $this->suspension->enforcement_action_id,
            'suspension_status' => (string) $this->suspension->status,
            'appeal_status' => (string) $this->suspension->appeal_status,
            'starts_at' => optional($this->suspension->starts_at)?->toDateTimeString(),
            'ends_at' => optional($this->suspension->ends_at)?->toDateTimeString(),
        ];
    }
}
