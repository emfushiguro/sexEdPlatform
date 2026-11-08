<?php

namespace App\Notifications;

use App\Enums\LearnerIdentityRejectionReason;
use App\Models\LearnerIdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearnerIdentityRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LearnerIdentityVerification $case,
        private readonly LearnerIdentityRejectionReason $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject('Update on Your Identity Verification')
            ->view('emails.moderation-status', [
                'title' => 'Identity Verification Update',
                'subtitle' => $this->case->resubmission_allowed ? 'Please update your submission' : 'Your review is complete',
                'greetingName' => $notifiable->first_name ?? 'Learner',
                'intro' => 'We could not approve your identity verification yet.',
                'details' => ['Reason: '.$this->reason->label(), $this->case->resubmission_allowed ? 'Please review the requested corrections and submit updated evidence.' : 'Contact support if you need help with this decision.'],
                'actionUrl' => route('learner.identity.status'),
                'actionText' => 'View next steps',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learner_identity_rejected',
            'status' => 'rejected',
            'title' => $this->case->resubmission_allowed ? 'Identity verification needs an update' : 'Identity verification rejected',
            'message' => $this->case->resubmission_allowed ? 'Please review the requested corrections and update your identity evidence.' : 'Contact support if you need help with this decision.',
            'reason' => $this->reason->label(),
            'verification_id' => $this->case->id,
            'action_url' => route('learner.identity.status'),
            'severity' => 'error',
        ];
    }
}
