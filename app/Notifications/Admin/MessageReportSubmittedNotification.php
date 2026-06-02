<?php

namespace App\Notifications\Admin;

use App\Enums\ModerationCaseSource;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly MessageReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $moderationCase = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::ChatReport->value)
            ->where('content_type', 'message_report')
            ->where('content_id', $this->report->id)
            ->first();

        return [
            'type' => 'message_report_submitted',
            'status' => 'submitted',
            'title' => 'New Chat Message Report',
            'message' => 'A chat message was reported for moderation review.',
            'message_report_id' => $this->report->id,
            'message_id' => $this->report->message_id,
            'conversation_id' => $this->report->conversation_id,
            'reason_code' => $this->report->reason_code,
            'action_url' => $moderationCase
                ? route('admin.moderation-suspensions.reports.show', $moderationCase)
                : route('admin.moderation-suspensions.index'),
            'severity' => 'warning',
        ];
    }
}
