<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorFinancialReportFilterRequest;
use App\Models\ReportGenerationLog;
use App\Services\Finance\FinancialReportExportService;
use App\Services\Finance\FinancialReportFilterNormalizer;
use App\Services\Finance\FinancialReportService;
use App\Services\Instructor\InstructorPlanCapabilityService;

class InstructorFinancialReportExportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $financialReportService,
        private readonly FinancialReportFilterNormalizer $filterNormalizer,
        private readonly FinancialReportExportService $exportService,
        private readonly InstructorPlanCapabilityService $instructorPlanCapabilityService,
    ) {
    }

    public function export(InstructorFinancialReportFilterRequest $request, string $format)
    {
        $user = $request->user();

        if (
            $this->instructorPlanCapabilityService->isStrictRolloutMode()
            && !$this->instructorPlanCapabilityService->canViewEarnings($user)
        ) {
            abort(403, 'Your current instructor plan does not include earnings visibility.');
        }

        $filter = $this->filterNormalizer->normalize(
            filters: $request->validated(),
            forcedInstructorId: (int) $user->id,
        );

        $summaryPayload = [
            'filter' => $filter->toArray(),
            'summary' => $this->financialReportService->getInstructorEarnings($filter)['summary'],
            'trend' => $this->financialReportService->getSummary($filter)['trend'],
        ];

        $breakdownPayload = $this->financialReportService->getRevenueBreakdown($filter);

        ReportGenerationLog::query()->create([
            'generated_at' => now(),
            'generated_by_user_id' => $user?->id,
            'generated_by_role' => (string) ($user?->role ?? 'instructor'),
            'report_scope' => 'instructor',
            'export_format' => $format,
            'filters_json' => $filter->toArray(),
            'checksum_hash' => $filter->checksum(),
            'row_count' => count($breakdownPayload['top_modules'] ?? []),
            'summary_snapshot_json' => $summaryPayload['summary'] ?? [],
        ]);

        return $this->exportService->exportInstructorReport(
            format: $format,
            filter: $filter,
            summaryPayload: $summaryPayload,
            breakdownPayload: $breakdownPayload,
        );
    }
}
