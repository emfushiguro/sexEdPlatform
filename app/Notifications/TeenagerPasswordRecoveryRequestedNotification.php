<?php

namespace App\Notifications;

use App\Models\MinorAccountHandoff;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeenagerPasswordRecoveryRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $child,
        private readonly MinorAccountHandoff $handoff,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Teenager account recovery request')
            ->line('A Teenager connected to your guardian account requested help recovering access.')
            ->line('Review the request from your dependent dashboard. The Teenager will choose the new password.')
            ->action('Review request', route('parent.children.show', $this->child))
            ->line('This request expires '.$this->handoff->expires_at?->format('M d, Y H:i T').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'teenager_account_recovery_requested',
            'title' => 'Teenager account recovery requested',
            'message' => 'A Teenager connected to your account requested recovery help.',
            'child_user_id' => $this->child->id,
            'handoff_id' => $this->handoff->id,
            'action_url' => route('parent.children.show', $this->child),
            'expires_at' => $this->handoff->expires_at?->toIso8601String(),
        ];
    }
}
