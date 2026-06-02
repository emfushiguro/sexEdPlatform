<?php

namespace App\Notifications\Seminars;

use App\Models\SeminarSpeaker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarSpeakerAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SeminarSpeaker $speaker)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $seminar = $this->speaker->seminar;

        return (new MailMessage)
            ->subject('Seminar speaker assignment')
            ->line('You have been assigned as a speaker for '.$seminar->title.'.')
            ->line('Hosted by: '.($seminar->connector?->name ?? 'Concious Connections'))
            ->line('Schedule: '.$this->formattedSchedule())
            ->action('View seminar', route('seminars.show', $seminar));
    }

    public function toDatabase(object $notifiable): array
    {
        $seminar = $this->speaker->seminar;

        return [
            'type' => 'seminar_speaker_assigned',
            'title' => 'Seminar speaker assignment',
            'message' => 'You have been assigned as a speaker for '.$seminar->title.'.',
            'seminar_id' => $seminar->id,
            'seminar_title' => $seminar->title,
            'seminar_speaker_id' => $this->speaker->id,
            'connector_id' => $seminar->connector_id,
            'connector_name' => $seminar->connector?->name,
            'starts_at' => optional($seminar->starts_at ?? $seminar->schedule)?->toDateTimeString(),
            'action_url' => route('seminars.show', $seminar),
            'severity' => 'info',
        ];
    }

    private function formattedSchedule(): string
    {
        $seminar = $this->speaker->seminar;
        $startsAt = $seminar->starts_at ?? $seminar->schedule;
        $endsAt = $seminar->ends_at;

        if (! $startsAt) {
            return 'To be announced';
        }

        return $startsAt->format('M d, Y h:i A').($endsAt ? ' - '.$endsAt->format('h:i A') : '');
    }
}
