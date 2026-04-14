<?php

namespace App\Notifications\Instructor;

use App\Enums\ContentReportStatus;
use App\Models\ContentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InstructorReportOutcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ContentReport $report,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $statusValue = $this->report->status instanceof ContentReportStatus
            ? $this->report->status->value
            : (string) $this->report->status;

        return [
            'type' => 'instructor_report_outcome',
            'status' => $statusValue,
            'title' => 'Moderation Update on Learner Report',
            'message' => 'A learner report involving your account was reviewed. ' . (string) ($this->report->latest_outcome_message ?: ''),
            'report_id' => $this->report->id,
            'action_url' => route('instructor.modules.index'),
            'severity' => 'warning',
        ];
    }
}
