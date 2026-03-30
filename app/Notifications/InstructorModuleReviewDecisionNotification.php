<?php

namespace App\Notifications;

use App\Models\ModuleReviewRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstructorModuleReviewDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $status,
        public readonly ModuleReviewRequest $reviewRequest,
        public readonly ?string $reasonCode = null,
        public readonly ?string $guidanceNote = null,
        public readonly ?string $penaltySummary = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'module_review_decision',
            'status' => $this->status,
            'module_id' => $this->reviewRequest->module_id,
            'module_title' => $this->reviewRequest->module_title,
            'reason_code' => $this->reasonCode,
            'guidance_note' => $this->guidanceNote,
            'penalty_summary' => $this->penaltySummary,
            'review_request_id' => $this->reviewRequest->id,
            'url' => route('instructor.modules.show', $this->reviewRequest->module_id),
            'title' => $this->status === 'approved' ? 'Module Approved' : 'Module Needs Revision',
            'message' => $this->status === 'approved'
                ? 'Your module has been approved and published for learners.'
                : 'Your module requires revision. Review the guidance and resubmit.',
        ];
    }
}
