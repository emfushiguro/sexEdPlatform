<?php

namespace App\Notifications\Learner;

use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Models\ContentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContentReportStatusNotification extends Notification
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

        $targetTypeValue = $this->report->target_type instanceof ContentReportTargetType
            ? $this->report->target_type->value
            : (string) $this->report->target_type;

        return [
            'type' => 'content_report_status_update',
            'status' => $statusValue,
            'title' => 'Your Report Was Updated',
            'message' => (string) ($this->report->latest_outcome_message ?: 'Your report has a new moderation status.'),
            'report_id' => $this->report->id,
            'target_type' => $targetTypeValue,
            'target_id' => (int) $this->report->target_id,
            'action_url' => route('learner.modules.index'),
            'severity' => $statusValue === ContentReportStatus::Dismissed->value ? 'info' : 'success',
        ];
    }
}
