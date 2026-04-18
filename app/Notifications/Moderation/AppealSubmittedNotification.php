<?php

namespace App\Notifications\Moderation;

use App\Models\SuspensionAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppealSubmittedNotification extends Notification
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
        return (new MailMessage)
            ->subject('New Suspension Appeal Submitted')
            ->line('A learner submitted a new suspension appeal for moderation review.')
            ->line('Appeal ID: ' . $this->appeal->id)
            ->line('Current status: ' . str_replace('_', ' ', (string) $this->appeal->status))
            ->action('Review Appeal', route('admin.moderation-appeals.show', $this->appeal));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'moderation_appeal_submitted',
            'appeal_id' => $this->appeal->id,
            'appeal_status' => (string) $this->appeal->status,
            'suspension_id' => $this->appeal->user_suspension_id,
            'user_id' => $this->appeal->user_id,
            'submitted_at' => optional($this->appeal->submitted_at)?->toDateTimeString(),
        ];
    }
}
