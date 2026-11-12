<?php

namespace App\Notifications;

use App\Models\MinorRegistrationRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class GuardianMinorRegistrationInvitation extends Notification
{
    public function __construct(
        private readonly MinorRegistrationRequest $registrationRequest,
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->registrationRequest->minor_payload ?? [];
        $name = trim((string) (($payload['first_name'] ?? '').' '.($payload['last_name'] ?? '')));

        return (new MailMessage)
            ->subject('A Teenager requested guardian help')
            ->view('emails.guardian-minor-registration-invitation', [
                'name' => $name !== '' ? $name : 'the Teenager',
                'acceptUrl' => URL::route('minor.registration.accept', ['token' => $this->token]),
                'expiresAt' => $this->registrationRequest->expires_at,
            ]);
    }
}
