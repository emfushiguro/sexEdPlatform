<?php

namespace App\Services\Moderation\SourceAdapters;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\MessageReport;
use App\Services\Moderation\ModerationCaseIntakeService;

class ChatReportModerationAdapter
{
    public function __construct(private readonly ModerationCaseIntakeService $moderationCaseIntakeService)
    {
    }

    public function syncReport(MessageReport $messageReport): void
    {
        $messageReport->loadMissing('message:id,sender_id,conversation_id');

        $reportedUserId = (int) ($messageReport->message?->sender_id ?? $messageReport->reporter_id);

        $this->moderationCaseIntakeService->upsertFromSource(
            source: ModerationCaseSource::ChatReport,
            contentType: 'message_report',
            contentId: (int) $messageReport->id,
            reportedUserId: $reportedUserId,
            reporterId: (int) $messageReport->reporter_id,
            status: $this->resolveStatus((string) $messageReport->status),
            decision: $this->resolveDecision((string) $messageReport->status),
            metadata: [
                'source_trace' => [
                    'source_record_id' => (int) $messageReport->id,
                    'message_id' => (int) $messageReport->message_id,
                    'conversation_id' => (int) $messageReport->conversation_id,
                    'report_status' => (string) $messageReport->status,
                    'reason' => $messageReport->reason,
                    'reported_at' => optional($messageReport->created_at)?->toDateTimeString(),
                    'updated_at' => optional($messageReport->updated_at)?->toDateTimeString(),
                ],
            ],
        );
    }

    private function resolveStatus(string $reportStatus): ModerationCaseStatus
    {
        return match ($reportStatus) {
            'under_review' => ModerationCaseStatus::Investigating,
            'resolved', 'dismissed', 'closed' => ModerationCaseStatus::Resolved,
            default => ModerationCaseStatus::Reported,
        };
    }

    private function resolveDecision(string $reportStatus): ?string
    {
        return match ($reportStatus) {
            'resolved', 'dismissed', 'closed' => $reportStatus,
            default => null,
        };
    }
}
