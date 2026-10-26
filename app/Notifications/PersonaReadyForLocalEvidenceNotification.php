<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PersonaReadyForLocalEvidenceNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $actionUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject('Persona evidence is ready')
            ->greeting('Hello '.($notifiable->first_name ?: 'there').',')
            ->line('Your simulated Persona Sandbox evidence step is ready for the next identity step.')
            ->line('An authorized administrator still reviews and decides the application.')
            ->action('Continue identity verification', $this->actionUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'persona_ready_for_local_evidence',
            'status' => 'eligible',
            'title' => 'Persona evidence is ready',
            'message' => 'Continue identity verification. An administrator still reviews the application.',
            'action_url' => $this->actionUrl,
            'severity' => 'info',
        ];
    }
}
