<?php

namespace App\Notifications\Seminars;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarRegistrationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Seminar $seminar,
        private readonly string $reason,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seminar registration update')
            ->line('Your registration for '.$this->seminar->title.' was not approved.')
            ->line('Reason: '.$this->reason)
            ->action('View seminars', route('seminars.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'seminar_registration_rejected',
            'title' => 'Seminar registration not approved',
            'message' => 'Your registration for '.$this->seminar->title.' was not approved.',
            'reason' => $this->reason,
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'action_url' => route('seminars.index'),
            'severity' => 'warning',
        ];
    }
}
