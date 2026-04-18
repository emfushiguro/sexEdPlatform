<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinancialReportExportRequest;
use App\Http\Requests\Admin\FinancialReportFilterRequest;
use App\Models\ReportGenerationLog;
use App\Services\AdminActivityLogService;
use App\Services\Finance\FinancialReportExportService;
use App\Services\Finance\FinancialReportFilterNormalizer;
use App\Services\Finance\FinancialReportService;
use App\Support\Finance\FinancialReportFilter;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $financialReportService,
        private readonly FinancialReportFilterNormalizer $filterNormalizer,
        private readonly FinancialReportExportService $exportService,
        private readonly AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function index(FinancialReportFilterRequest $request)
    {
        $filter = $this->filterNormalizer->normalize($request->validated());
        $summaryPayload = $this->financialReportService->getSummary($filter);
        $breakdownPayload = $this->financialReportService->getRevenueBreakdown($filter);

        $this->logReportGeneration(
            request: $request,
            filter: $filter,
            exportFormat: 'view',
            summaryPayload: $summaryPayload,
            rowCount: count($breakdownPayload['top_modules'] ?? []) + count($breakdownPayload['top_instructors'] ?? []),
        );

        return view('admin.financial-reports.index', [
            'reportFilter' => $filter,
            'summaryPayload' => $summaryPayload,
            'breakdownPayload' => $breakdownPayload,
        ]);
    }

    public function export(FinancialReportExportRequest $request, string $format)
    {
        $filter = $this->filterNormalizer->normalize($request->validated());
        $summaryPayload = $this->financialReportService->getSummary($filter);
        $breakdownPayload = $this->financialReportService->getRevenueBreakdown($filter);

        $this->logReportGeneration(
            request: $request,
            filter: $filter,
            exportFormat: $format,
            summaryPayload: $summaryPayload,
            rowCount: count($breakdownPayload['top_modules'] ?? []) + count($breakdownPayload['top_instructors'] ?? []),
        );

        return $this->exportService->exportAdminReport(
            format: $format,
            filter: $filter,
            summaryPayload: $summaryPayload,
            breakdownPayload: $breakdownPayload,
        );
    }

    private function logReportGeneration(
        Request $request,
        FinancialReportFilter $filter,
        string $exportFormat,
        array $summaryPayload,
        ?int $rowCount = null,
    ): void {
        $user = $request->user();

        ReportGenerationLog::query()->create([
            'generated_at' => now(),
            'generated_by_user_id' => $user?->id,
            'generated_by_role' => (string) ($user?->role ?? 'unknown'),
            'report_scope' => 'admin',
            'export_format' => $exportFormat,
            'filters_json' => $filter->toArray(),
            'checksum_hash' => $filter->checksum(),
            'row_count' => $rowCount,
            'summary_snapshot_json' => $summaryPayload['summary'] ?? [],
        ]);

        $this->adminActivityLogService->log(
            action: 'financial_reports.generate',
            entityType: ReportGenerationLog::class,
            entityId: null,
            before: null,
            after: null,
            meta: [
                'scope' => 'admin',
                'format' => $exportFormat,
                'filters' => $filter->toArray(),
                'summary' => $summaryPayload['summary'] ?? [],
            ],
            request: $request,
        );
    }
}
