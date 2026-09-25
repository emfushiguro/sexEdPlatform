<?php

namespace App\Notifications;

use App\Models\LearnerIdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearnerIdentitySubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LearnerIdentityVerification $case) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject('Identity Verification Submitted')
            ->view('emails.moderation-status', [
                'title' => 'Identity Verification Submitted',
                'subtitle' => 'Pending manual review',
                'greetingName' => $notifiable->first_name ?? 'Learner',
                'intro' => 'We received your ID and selfie for identity review.',
                'details' => ['An authorized reviewer will check your submission. We will let you know the result.'],
                'actionUrl' => route('learner.identity.status'),
                'actionText' => 'View status',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learner_identity_submitted',
            'status' => 'pending',
            'title' => 'Identity verification submitted',
            'message' => 'Your identity evidence is pending manual review.',
            'verification_id' => $this->case->id,
            'action_url' => route('learner.identity.status'),
            'severity' => 'info',
        ];
    }
}
