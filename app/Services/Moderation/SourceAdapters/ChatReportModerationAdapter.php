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
        $messageReport->loadMissing([
            'message:id,sender_id,conversation_id,message_body',
            'message.sender:id,name,email,role',
            'conversation:id,participant_one_id,participant_two_id,conversation_type,context_key',
            'conversation.participantOne:id,name,email,role',
            'conversation.participantTwo:id,name,email,role',
            'reporter:id,name,email,role',
        ]);

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
                    'reason_code' => $messageReport->reason_code,
                    'custom_reason' => $messageReport->custom_reason,
                    'reason' => $messageReport->reason,
                    'message_body' => $messageReport->message?->message_body,
                    'sender_id' => $messageReport->message?->sender_id,
                    'sender_email' => $messageReport->message?->sender?->email,
                    'receiver_ids' => array_values(array_filter([
                        $messageReport->conversation?->participant_one_id,
                        $messageReport->conversation?->participant_two_id,
                    ], fn ($id) => (int) $id !== (int) $messageReport->message?->sender_id)),
                    'action_taken' => $messageReport->action_taken,
                    'moderation_notes' => $messageReport->moderation_notes,
                    'reviewed_by_admin_id' => $messageReport->reviewed_by_admin_id,
                    'reviewed_at' => optional($messageReport->reviewed_at)?->toDateTimeString(),
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
