<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstructorIdentitySubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly int $caseId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'instructor_identity_submitted',
            'status' => 'pending_review',
            'title' => 'New Instructor Identity Review',
            'message' => 'A direct instructor applicant submitted identity evidence for review.',
            'instructor_identity_verification_id' => $this->caseId,
            'action_url' => route('admin.instructor-applications.identities.show', $this->caseId),
            'severity' => 'info',
        ];
    }
}
