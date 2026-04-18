<?php

namespace App\Notifications\Moderation;

use App\Models\SuspensionAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppealDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly SuspensionAppeal $appeal)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = str_replace('_', ' ', (string) $this->appeal->status);

        return (new MailMessage)
            ->subject('Suspension Appeal Decision Update')
            ->line('Your suspension appeal has been updated.')
            ->line('Current status: ' . $status)
            ->line('Decision notes: ' . (string) ($this->appeal->review_decision_notes ?? 'No additional notes provided.'))
            ->action('View Suspension Status', route('moderation.suspension-status'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'moderation_appeal_decision',
            'appeal_id' => $this->appeal->id,
            'suspension_id' => $this->appeal->user_suspension_id,
            'user_id' => $this->appeal->user_id,
            'appeal_status' => (string) $this->appeal->status,
            'decision_notes' => $this->appeal->review_decision_notes,
            'reviewed_by_admin_id' => $this->appeal->reviewed_by_admin_id,
            'reviewed_at' => optional($this->appeal->reviewed_at)?->toDateTimeString(),
        ];
    }
}
