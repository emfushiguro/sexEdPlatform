<?php

namespace App\Services\Moderation\SourceAdapters;

use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\ContentReport;
use App\Models\Module;
use App\Services\Moderation\ModerationCaseIntakeService;

class LearnerReportModerationAdapter
{
    public function __construct(private readonly ModerationCaseIntakeService $moderationCaseIntakeService)
    {
    }

    public function syncReport(ContentReport $report): void
    {
        $targetType = $this->resolveTargetType($report);
        $status = $this->resolveStatus($report);

        $this->moderationCaseIntakeService->upsertFromSource(
            source: ModerationCaseSource::LearnerReport,
            contentType: 'content_report',
            contentId: (int) $report->id,
            reportedUserId: $this->resolveReportedUserId($report, $targetType),
            reporterId: (int) $report->reporter_id,
            status: $status,
            decision: $this->resolveDecision($status, $report),
            metadata: [
                'source_trace' => [
                    'source_record_id' => (int) $report->id,
                    'target_type' => $targetType->value,
                    'target_id' => (int) $report->target_id,
                    'reason_code' => (string) $report->reason_code,
                    'status' => $this->resolveReportStatus($report)->value,
                    'assigned_admin_id' => $report->assigned_admin_id,
                    'resolved_by' => $report->resolved_by,
                    'resolved_at' => optional($report->resolved_at)?->toDateTimeString(),
                    'dismissed_at' => optional($report->dismissed_at)?->toDateTimeString(),
                    'updated_at' => optional($report->updated_at)?->toDateTimeString(),
                ],
            ],
        );
    }

    private function resolveReportedUserId(ContentReport $report, ContentReportTargetType $targetType): int
    {
        if ($targetType === ContentReportTargetType::Module) {
            $moduleOwnerId = (int) Module::query()
                ->whereKey((int) $report->target_id)
                ->value('created_by');

            return $moduleOwnerId > 0
                ? $moduleOwnerId
                : (int) $report->reporter_id;
        }

        return (int) $report->target_id;
    }

    private function resolveTargetType(ContentReport $report): ContentReportTargetType
    {
        return $report->target_type instanceof ContentReportTargetType
            ? $report->target_type
            : ContentReportTargetType::from((string) $report->target_type);
    }

    private function resolveStatus(ContentReport $report): ModerationCaseStatus
    {
        return match ($this->resolveReportStatus($report)) {
            ContentReportStatus::UnderReview => ModerationCaseStatus::Investigating,
            ContentReportStatus::Resolved, ContentReportStatus::Dismissed => ModerationCaseStatus::Resolved,
            default => ModerationCaseStatus::Reported,
        };
    }

    private function resolveDecision(ModerationCaseStatus $status, ContentReport $report): ?string
    {
        if ($status !== ModerationCaseStatus::Resolved) {
            return null;
        }

        return $this->resolveReportStatus($report)->value;
    }

    private function resolveReportStatus(ContentReport $report): ContentReportStatus
    {
        return $report->status instanceof ContentReportStatus
            ? $report->status
            : ContentReportStatus::from((string) $report->status);
    }
}
