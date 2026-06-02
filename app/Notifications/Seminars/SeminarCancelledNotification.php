<?php

namespace App\Notifications\Seminars;

use App\Models\Seminar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SeminarCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Seminar $seminar,
        private readonly ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Seminar cancelled')
            ->line($this->seminar->title.' has been cancelled.')
            ->line('Hosted by: '.($this->seminar->connector?->name ?? 'Concious Connections'))
            ->line('Schedule: '.$this->formattedSchedule());

        if (filled($this->reason)) {
            $message->line('Reason: '.$this->reason);
        }

        return $message->action('View seminar', route('seminars.show', $this->seminar));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'seminar_cancelled',
            'title' => 'Seminar cancelled',
            'message' => $this->seminar->title.' has been cancelled.',
            'seminar_id' => $this->seminar->id,
            'seminar_title' => $this->seminar->title,
            'connector_id' => $this->seminar->connector_id,
            'connector_name' => $this->seminar->connector?->name,
            'starts_at' => optional($this->seminar->starts_at ?? $this->seminar->schedule)?->toDateTimeString(),
            'reason' => $this->reason,
            'action_url' => route('seminars.show', $this->seminar),
            'severity' => 'warning',
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
