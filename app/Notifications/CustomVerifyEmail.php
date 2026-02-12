<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class CustomVerifyEmail extends BaseVerifyEmail
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address - ' . config('app.name'))
            ->greeting('Welcome to ' . config('app.name') . '!')
            ->line('Thank you for creating an account with us. We\'re excited to have you join our learning community.')
            ->line('To get started and access age-appropriate sexual education content, please verify your email address by clicking the button below:')
            ->action('Verify Email Address', $verificationUrl)
            ->line('This verification link will expire in 60 minutes.')
            ->line(new HtmlString('<strong>What happens next?</strong>'))
            ->line('• Once verified, you\'ll complete your profile with a username and location')
            ->line('• Access age-appropriate educational modules and lessons')
            ->line('• Track your learning progress and earn achievements')
            ->line('• Take quizzes and receive certificates upon completion')
            ->line('If you did not create an account, no further action is required. Your email will not be added to our system.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }
}
