<?php

namespace App\Notifications\Seminars;

use App\Models\SeminarSpeaker;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarSpeakerAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SeminarSpeaker $speaker) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $seminar = $this->speaker->seminar;

        return (new MailMessage)
            ->subject('Seminar speaker invitation')
            ->line('You have been invited as a speaker for '.$seminar->title.'.')
            ->line('Hosted by: '.($seminar->connector?->name ?? 'Concious Connections'))
            ->line('Schedule: '.$this->formattedSchedule())
            ->when($this->speaker->invitation_message, fn (MailMessage $mail) => $mail->line('Message: '.$this->speaker->invitation_message))
            ->action('Review invitation', route('instructor.speaker-invitations.show', $this->speaker));
    }

    public function toDatabase(object $notifiable): array
    {
        $seminar = $this->speaker->seminar;

        return [
            'type' => 'seminar_speaker_invitation',
            'title' => 'Seminar speaker invitation',
            'message' => 'You have been invited as a speaker for '.$seminar->title.'.',
            'seminar_id' => $seminar->id,
            'seminar_title' => $seminar->title,
            'seminar_speaker_id' => $this->speaker->id,
            'connector_id' => $seminar->connector_id,
            'connector_name' => $seminar->connector?->name,
            'starts_at' => optional($seminar->starts_at ?? $seminar->schedule)?->toISOString(),
            'invitation_message' => $this->speaker->invitation_message,
            'action_url' => route('instructor.speaker-invitations.show', $this->speaker),
            'severity' => 'info',
        ];
    }

    private function formattedSchedule(): string
    {
        return $this->speaker->seminar->formattedSchedule();
    }
}
