<?php

namespace App\Notifications\Admin;

use App\Models\LearnerIdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LearnerIdentitySubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LearnerIdentityVerification $case) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'learner_identity_submitted',
            'status' => 'pending',
            'title' => 'New Learner Identity Review',
            'message' => 'A learner submitted identity evidence for manual review.',
            'verification_id' => $this->case->id,
            'action_url' => route('admin.parent-verifications.learners.show', $this->case),
            'severity' => 'info',
        ];
    }
}
