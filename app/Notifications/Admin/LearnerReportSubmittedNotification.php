<?php

namespace App\Notifications\Admin;

use App\Enums\ContentReportTargetType;
use App\Models\ContentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LearnerReportSubmittedNotification extends Notification
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
        $targetTypeValue = $this->report->target_type instanceof ContentReportTargetType
            ? $this->report->target_type->value
            : (string) $this->report->target_type;

        $targetLabel = $targetTypeValue === ContentReportTargetType::Module->value ? 'module' : 'instructor';

        return [
            'type' => 'learner_report_submitted',
            'status' => 'submitted',
            'title' => 'New Learner Report Submitted',
            'message' => 'A learner submitted a report about a ' . $targetLabel . '. Reason: ' . str_replace('_', ' ', (string) $this->report->reason_code) . '.',
            'report_id' => $this->report->id,
            'target_type' => $targetTypeValue,
            'target_id' => (int) $this->report->target_id,
            'action_url' => route('admin.learner-reports.show', $this->report),
            'severity' => 'warning',
        ];
    }
}
