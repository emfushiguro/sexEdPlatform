<?php

namespace App\Services\Moderation\SourceAdapters;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\InstructorApplication;
use App\Services\Moderation\ModerationCaseIntakeService;

class InstructorApplicationModerationAdapter
{
    public function __construct(private readonly ModerationCaseIntakeService $moderationCaseIntakeService)
    {
    }

    public function syncSubmission(InstructorApplication $application): void
    {
        $status = $this->resolveStatus((string) $application->status);

        $this->moderationCaseIntakeService->upsertFromSource(
            source: ModerationCaseSource::InstructorApplication,
            contentType: 'instructor_application',
            contentId: (int) $application->id,
            reportedUserId: (int) $application->user_id,
            reporterId: (int) $application->user_id,
            status: $status,
            decision: $this->resolveDecision((string) $application->status),
            metadata: [
                'source_trace' => [
                    'source_record_id' => (int) $application->id,
                    'user_id' => (int) $application->user_id,
                    'application_status' => (string) $application->status,
                    'approved_by' => $application->approved_by,
                    'approved_at' => optional($application->approved_at)?->toDateTimeString(),
                    'rejection_reason_code' => $application->rejection_reason_code,
                    'updated_at' => optional($application->updated_at)?->toDateTimeString(),
                ],
            ],
        );
    }

    private function resolveStatus(string $applicationStatus): ModerationCaseStatus
    {
        return match ($applicationStatus) {
            'approved', 'rejected' => ModerationCaseStatus::Resolved,
            default => ModerationCaseStatus::Reported,
        };
    }

    private function resolveDecision(string $applicationStatus): ?string
    {
        return match ($applicationStatus) {
            'approved', 'rejected' => $applicationStatus,
            default => null,
        };
    }
}
