<?php

namespace App\Notifications\Seminars;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $seminarId,
        public readonly string $title,
        public readonly string $kind,
    ) {
        if (! in_array($kind, ['changed', 'available'], true)) {
            throw new \InvalidArgumentException('Unsupported delivery notice kind.');
        }
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->kind === 'changed' ? 'Event access details changed' : 'Event access is available')
            ->line($this->title.($this->kind === 'changed' ? ' has updated access details.' : ' is ready to join.'))
            ->line('Open the event page for current access details.')
            ->action('Open event', route('seminars.show', $this->seminarId));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'seminar_delivery_'.$this->kind,
            'title' => $this->kind === 'changed' ? 'Event access details changed' : 'Event access is available',
            'message' => $this->title.($this->kind === 'changed' ? ' has updated access details.' : ' is ready to join.'),
            'seminar_id' => $this->seminarId,
            'action_url' => route('seminars.show', $this->seminarId),
            'severity' => 'info',
        ];
    }
}
