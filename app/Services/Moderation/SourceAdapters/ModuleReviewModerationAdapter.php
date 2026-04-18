<?php

namespace App\Services\Moderation\SourceAdapters;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\ModuleReviewRequest;
use App\Models\User;
use App\Services\Moderation\ModerationCaseIntakeService;

class ModuleReviewModerationAdapter
{
    public function __construct(private readonly ModerationCaseIntakeService $moderationCaseIntakeService)
    {
    }

    public function syncSubmission(ModuleReviewRequest $reviewRequest, User $actor): void
    {
        $reviewRequest->loadMissing('module:id,created_by');

        $reportedUserId = (int) ($reviewRequest->module?->created_by ?? $actor->id);

        $this->moderationCaseIntakeService->upsertFromSource(
            source: ModerationCaseSource::ModuleReview,
            contentType: 'module_review_request',
            contentId: (int) $reviewRequest->id,
            reportedUserId: $reportedUserId,
            reporterId: (int) ($reviewRequest->submitted_by ?? $actor->id),
            status: $this->resolveStatus((string) $reviewRequest->status),
            decision: $this->resolveDecision((string) $reviewRequest->status),
            metadata: [
                'source_trace' => [
                    'source_record_id' => (int) $reviewRequest->id,
                    'module_id' => (int) $reviewRequest->module_id,
                    'module_revision_id' => (int) $reviewRequest->module_revision_id,
                    'submission_status' => (string) $reviewRequest->status,
                    'submitted_by' => (int) ($reviewRequest->submitted_by ?? $actor->id),
                    'submitted_at' => optional($reviewRequest->submitted_at)?->toDateTimeString(),
                    'reviewed_by' => $reviewRequest->reviewed_by,
                    'reviewed_at' => optional($reviewRequest->reviewed_at)?->toDateTimeString(),
                ],
            ],
        );
    }

    private function resolveStatus(string $submissionStatus): ModerationCaseStatus
    {
        return match ($submissionStatus) {
            'in_review' => ModerationCaseStatus::Investigating,
            'approved', 'rejected', 'withdrawn' => ModerationCaseStatus::Resolved,
            default => ModerationCaseStatus::Reported,
        };
    }

    private function resolveDecision(string $submissionStatus): ?string
    {
        return match ($submissionStatus) {
            'approved', 'rejected', 'withdrawn' => $submissionStatus,
            default => null,
        };
    }
}
