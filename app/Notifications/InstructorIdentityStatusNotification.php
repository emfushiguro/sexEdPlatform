<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstructorIdentityStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $caseId,
        public readonly string $status,
        public readonly ?string $reasonLabel = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'instructor_identity_'.$this->status,
            'status' => $this->status,
            'title' => match ($this->status) {
                'approved' => 'Identity review approved',
                'rejected' => 'Identity review rejected',
                'action_required' => 'Identity review needs attention',
                default => 'Identity review submitted',
            },
            'message' => $this->reasonLabel ?? 'Review your instructor application workspace for the next step.',
            'reason_label' => $this->reasonLabel,
            'instructor_identity_verification_id' => $this->caseId,
            'action_url' => route('instructor-application.dashboard'),
            'severity' => $this->status === 'approved' ? 'success' : ($this->status === 'rejected' ? 'error' : 'info'),
        ];
    }
}
