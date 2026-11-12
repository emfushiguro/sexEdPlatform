<?php

namespace App\Notifications;

use App\Models\MinorAccountHandoff;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class TeenagerActivationReadyNotification extends Notification
{
    public function __construct(
        private readonly User $child,
        private readonly MinorAccountHandoff $handoff,
        private readonly string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Teenager account activation is ready')
            ->view('emails.teenager-activation-ready', [
                'child' => $this->child,
                'code' => $this->code,
                'activationUrl' => URL::route('minor.activation.show', ['code' => $this->code]),
                'expiresAt' => $this->handoff->expires_at,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'teenager_activation_ready',
            'title' => 'Teenager account activation ready',
            'message' => 'The Teenager can now finish account activation.',
            'child_user_id' => $this->child->id,
            'handoff_id' => $this->handoff->id,
            'activation_url' => URL::route('minor.activation.show'),
            'expires_at' => $this->handoff->expires_at?->toIso8601String(),
        ];
    }
}
