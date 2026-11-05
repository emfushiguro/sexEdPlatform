<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PersonaActionRequiredNotification extends Notification
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
            ->subject('Identity verification needs attention')
            ->greeting('Hello '.($notifiable->first_name ?: 'there').',')
            ->line('Your simulated Persona Sandbox identity step needs attention before local evidence can be submitted.')
            ->line('Resume the step or start a new attempt from your identity status page.')
            ->action('Review identity status', $this->actionUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'persona_action_required',
            'status' => 'retryable',
            'title' => 'Identity verification needs attention',
            'message' => 'Review your identity status to continue.',
            'action_url' => $this->actionUrl,
            'severity' => 'warning',
        ];
    }
}
