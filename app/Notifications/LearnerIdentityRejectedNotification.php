<?php

namespace App\Notifications;

use App\Models\LearnerIdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LearnerIdentityRejectedNotification extends Notification
{
    use Queueable;

    private readonly string $reason;

    public function __construct(private readonly LearnerIdentityVerification $case, string $reason)
    {
        $this->reason = in_array(trim($reason), [
            'Please upload a clearer ID photo.',
            'Please upload a clearer selfie photo.',
            'Document is unreadable.',
        ], true) ? trim($reason) : 'Please check your identity submission and upload clearer images.';
    }

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
                'subtitle' => 'Please update your submission',
                'greetingName' => $notifiable->first_name ?? 'Learner',
                'intro' => 'We could not approve your identity verification yet.',
                'details' => ['Reason: '.$this->reason, 'Please update your ID or selfie and send it again.'],
                'actionUrl' => route('learner.identity.status'),
                'actionText' => 'View next steps',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'learner_identity_rejected',
            'status' => 'rejected',
            'title' => 'Identity verification needs an update',
            'message' => 'Please review the reason and update your identity evidence.',
            'reason' => $this->reason,
            'verification_id' => $this->case->id,
            'action_url' => route('learner.identity.status'),
            'severity' => 'error',
        ];
    }
}
