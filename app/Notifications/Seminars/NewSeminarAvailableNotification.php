<?php

namespace App\Notifications\Seminars;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSeminarAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Seminar $seminar)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $connectorName = $this->seminar->connector?->name ?? 'A connector';

        return (new MailMessage)
            ->subject('New Seminar Available')
            ->line($connectorName.' has published a new seminar. Registration is now open.')
            ->line($this->seminar->title)
            ->action('View seminar', route('seminars.show', $this->seminar));
    }

    public function toDatabase(object $notifiable): array
    {
        $connectorName = $this->seminar->connector?->name ?? 'A connector';

        return [
            'type' => 'new_seminar_available',
            'title' => 'New Seminar Available',
            'message' => $connectorName.' has published a new seminar. Registration is now open.',
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'connector_id' => $this->seminar->connector_id,
            'connector_name' => $this->seminar->connector?->name,
            'action_url' => route('seminars.show', $this->seminar),
            'severity' => 'info',
        ];
    }
}
