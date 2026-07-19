<?php

namespace App\Notifications\Seminars;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarLiveNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Seminar $seminar,
        private readonly string $role,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $speaker = $this->role === 'speaker';

        return (new MailMessage)
            ->subject($this->seminar->title.' is live')
            ->line($speaker ? 'The livestream has started. You may now join as a speaker.' : 'Your seminar is now LIVE.')
            ->action($speaker ? 'Join as speaker' : 'Join now', route('seminars.join', $this->seminar));
    }

    public function toDatabase(object $notifiable): array
    {
        $speaker = $this->role === 'speaker';

        return [
            'type' => 'seminar_live',
            'title' => $this->seminar->title.' is LIVE',
            'message' => $speaker ? 'The livestream has started. You may now join as a speaker.' : 'Your seminar is now LIVE. Join now.',
            'seminar_id' => $this->seminar->id,
            'role' => $this->role,
            'action_url' => route('seminars.join', $this->seminar),
            'severity' => 'success',
        ];
    }
}
