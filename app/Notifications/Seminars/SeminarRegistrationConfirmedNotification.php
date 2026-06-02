<?php

namespace App\Notifications\Seminars;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarRegistrationConfirmedNotification extends Notification
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
        return (new MailMessage)
            ->subject('Seminar registration confirmed')
            ->line('Your registration for '.$this->seminar->title.' is confirmed.')
            ->line('Hosted by: '.($this->seminar->connector?->name ?? 'Concious Connections'))
            ->line('Schedule: '.$this->formattedSchedule())
            ->action('View seminar', route('seminars.show', $this->seminar));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'seminar_registration_confirmed',
            'title' => 'Seminar registration confirmed',
            'message' => 'You are registered for '.$this->seminar->title.'.',
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'connector_id' => $this->seminar->connector_id,
            'connector_name' => $this->seminar->connector?->name,
            'starts_at' => optional($this->seminar->starts_at ?? $this->seminar->schedule)?->toDateTimeString(),
            'action_url' => route('seminars.show', $this->seminar),
            'severity' => 'success',
        ];
    }

    private function formattedSchedule(): string
    {
        $startsAt = $this->seminar->starts_at ?? $this->seminar->schedule;
        $endsAt = $this->seminar->ends_at;

        if (! $startsAt) {
            return 'To be announced';
        }

        return $startsAt->format('M d, Y h:i A').($endsAt ? ' - '.$endsAt->format('h:i A') : '');
    }
}
