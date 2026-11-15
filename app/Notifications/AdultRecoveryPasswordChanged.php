<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdultRecoveryPasswordChanged extends Notification
{
    public function via(object $notifiable): array
    {
        return $notifiable->email && $notifiable->hasVerifiedEmail() ? ['database', 'mail'] : ['database'];
    }
    public function toDatabase(object $notifiable): array
    {
        return ['type' => 'adult_recovery_password_changed', 'title' => 'Password changed', 'message' => 'Your password was changed using a recovery code. If this was not you, contact support.'];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Your password was changed')->line('Your password was changed using a recovery code.')->line('If this was not you, contact support immediately.');
    }
}
