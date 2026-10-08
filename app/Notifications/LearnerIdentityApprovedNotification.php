<?php

namespace App\Notifications;

use App\Models\LearnerIdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearnerIdentityApprovedNotification extends Notification
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
            ->subject('Identity Verification Approved')
            ->view('emails.moderation-status', [
                'title' => 'Identity Verification Approved',
                'subtitle' => 'Your review is complete',
                'greetingName' => $notifiable->first_name ?? 'Learner',
                'intro' => 'Your identity verification has been approved.',
                'details' => ['You can continue setting up your learner account.'],
                'actionUrl' => route('learner.identity.status'),
                'actionText' => 'Continue',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learner_identity_approved',
            'status' => 'approved',
            'title' => 'Identity verification approved',
            'message' => 'Your identity verification was approved.',
            'verification_id' => $this->case->id,
            'action_url' => route('learner.identity.status'),
            'severity' => 'success',
        ];
    }
}
