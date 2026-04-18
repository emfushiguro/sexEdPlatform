<?php

namespace App\Services\Moderation\Backfill;

use App\Enums\ModerationCaseSource;
use App\Models\ContentReport;
use App\Models\InstructorApplication;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\ModuleReviewRequest;
use App\Models\User;
use App\Services\Moderation\SourceAdapters\ChatReportModerationAdapter;
use App\Services\Moderation\SourceAdapters\InstructorApplicationModerationAdapter;
use App\Services\Moderation\SourceAdapters\LearnerReportModerationAdapter;
use App\Services\Moderation\SourceAdapters\ModuleReviewModerationAdapter;

class CentralizedModerationBackfillService
{
    public function __construct(
        private readonly ModuleReviewModerationAdapter $moduleReviewModerationAdapter,
        private readonly ChatReportModerationAdapter $chatReportModerationAdapter,
        private readonly LearnerReportModerationAdapter $learnerReportModerationAdapter,
        private readonly InstructorApplicationModerationAdapter $instructorApplicationModerationAdapter,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function backfill(int $chunkSize = 200): array
    {
        $processed = [
            'module_review' => 0,
            'chat_report' => 0,
            'learner_report' => 0,
            'instructor_application' => 0,
            'skipped' => 0,
        ];

        ModuleReviewRequest::query()
            ->with('module:id,created_by')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($requests) use (&$processed): void {
                foreach ($requests as $request) {
                    if (!$request instanceof ModuleReviewRequest) {
                        $processed['skipped']++;
                        continue;
                    }

                    $actorId = (int) ($request->submitted_by ?: ($request->module?->created_by ?? 0));
                    $actor = $actorId > 0 ? User::query()->find($actorId) : null;

                    if (!$actor) {
                        $processed['skipped']++;
                        continue;
                    }

                    $this->moduleReviewModerationAdapter->syncSubmission($request, $actor);
                    $processed['module_review']++;
                }
            });

        MessageReport::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($reports) use (&$processed): void {
                foreach ($reports as $report) {
                    if (!$report instanceof MessageReport) {
                        $processed['skipped']++;
                        continue;
                    }

                    $this->chatReportModerationAdapter->syncReport($report);
                    $processed['chat_report']++;
                }
            });

        ContentReport::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($reports) use (&$processed): void {
                foreach ($reports as $report) {
                    if (!$report instanceof ContentReport) {
                        $processed['skipped']++;
                        continue;
                    }

                    $this->learnerReportModerationAdapter->syncReport($report);
                    $processed['learner_report']++;
                }
            });

        InstructorApplication::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($applications) use (&$processed): void {
                foreach ($applications as $application) {
                    if (!$application instanceof InstructorApplication) {
                        $processed['skipped']++;
                        continue;
                    }

                    $this->instructorApplicationModerationAdapter->syncSubmission($application);
                    $processed['instructor_application']++;
                }
            });

        $processed['total'] = $processed['module_review']
            + $processed['chat_report']
            + $processed['learner_report']
            + $processed['instructor_application'];

        return $processed;
    }

    /**
     * @return array{sources: array<int, array<string, int|string>>, mismatches: array<int, array<string, int|string>>, matched: bool}
     */
    public function reconcileParity(): array
    {
        $sourceRows = [
            $this->buildSourceRow(
                source: 'module_review',
                legacyCount: ModuleReviewRequest::query()->count(),
                centralizedCount: $this->countCentralized(ModerationCaseSource::ModuleReview, 'module_review_request'),
            ),
            $this->buildSourceRow(
                source: 'chat_report',
                legacyCount: MessageReport::query()->count(),
                centralizedCount: $this->countCentralized(ModerationCaseSource::ChatReport, 'message_report'),
            ),
            $this->buildSourceRow(
                source: 'learner_report',
                legacyCount: ContentReport::query()->count(),
                centralizedCount: $this->countCentralized(ModerationCaseSource::LearnerReport, 'content_report'),
            ),
            $this->buildSourceRow(
                source: 'instructor_application',
                legacyCount: InstructorApplication::query()->count(),
                centralizedCount: $this->countCentralized(ModerationCaseSource::InstructorApplication, 'instructor_application'),
            ),
        ];

        $mismatches = array_values(array_filter($sourceRows, fn (array $row): bool => (int) $row['delta'] !== 0));

        return [
            'sources' => $sourceRows,
            'mismatches' => $mismatches,
            'matched' => $mismatches === [],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function buildSourceRow(string $source, int $legacyCount, int $centralizedCount): array
    {
        return [
            'source' => $source,
            'legacy_count' => $legacyCount,
            'centralized_count' => $centralizedCount,
            'delta' => $legacyCount - $centralizedCount,
        ];
    }

    private function countCentralized(ModerationCaseSource $source, string $contentType): int
    {
        return ModerationCase::query()
            ->where('case_source', $source->value)
            ->where('content_type', $contentType)
            ->count();
    }
}
