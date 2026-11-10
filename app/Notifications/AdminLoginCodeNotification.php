<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            )
            ->subject('Admin Login Verification — '.config('app.name'))
            ->view('emails.admin-mfa-code', [
                'user' => $notifiable,
                'code' => $this->code,
            ]);
    }
}
