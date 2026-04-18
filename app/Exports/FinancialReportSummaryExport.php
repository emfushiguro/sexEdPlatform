<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialReportSummaryExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $summaryPayload,
        private readonly array $breakdownPayload,
    ) {
    }

    public function sheets(): array
    {
        $summary = (array) ($this->summaryPayload['summary'] ?? []);

        $summaryRows = [
            ['Metric', 'Value'],
            ['Total Revenue', (string) ($summary['total_revenue'] ?? 0)],
            ['Gross Revenue', (string) ($summary['gross_revenue'] ?? 0)],
            ['Net Revenue', (string) ($summary['net_revenue'] ?? 0)],
            ['Refund Amount', (string) ($summary['refund_amount'] ?? 0)],
            ['Subscription Revenue', (string) ($summary['subscription_revenue'] ?? 0)],
            ['Module Revenue', (string) ($summary['module_revenue'] ?? 0)],
            ['Platform Earnings', (string) ($summary['platform_earnings'] ?? 0)],
            ['Instructor Earnings', (string) ($summary['instructor_earnings'] ?? 0)],
        ];

        $sourceRows = [['Source', 'Amount']];
        foreach ((array) ($this->breakdownPayload['source_breakdown'] ?? []) as $sourceItem) {
            $sourceRows[] = [
                (string) data_get($sourceItem, 'source', 'unknown'),
                (string) data_get($sourceItem, 'amount', 0),
            ];
        }

        $instructorRows = [['Instructor', 'Sales Count', 'Gross Amount', 'Commission', 'Instructor Earnings']];
        foreach ($this->asCollection($this->breakdownPayload['top_instructors'] ?? []) as $item) {
            $instructorRows[] = [
                (string) data_get($item, 'instructor.name', 'Unknown Instructor'),
                (string) data_get($item, 'sales_count', 0),
                (string) data_get($item, 'gross_amount', 0),
                (string) data_get($item, 'commission_amount', 0),
                (string) data_get($item, 'instructor_earnings_amount', 0),
            ];
        }

        $moduleRows = [['Module', 'Sales Count', 'Gross Amount', 'Commission', 'Instructor Earnings']];
        foreach ($this->asCollection($this->breakdownPayload['top_modules'] ?? []) as $item) {
            $moduleRows[] = [
                (string) data_get($item, 'module.title', 'Unknown Module'),
                (string) data_get($item, 'sales_count', 0),
                (string) data_get($item, 'gross_amount', 0),
                (string) data_get($item, 'commission_amount', 0),
                (string) data_get($item, 'instructor_earnings_amount', 0),
            ];
        }

        return [
            new FinancialReportBreakdownExport('Summary', $summaryRows),
            new FinancialReportBreakdownExport('Source Breakdown', $sourceRows),
            new FinancialReportBreakdownExport('Top Instructors', $instructorRows),
            new FinancialReportBreakdownExport('Top Modules', $moduleRows),
        ];
    }

    private function asCollection(mixed $value): Collection
    {
        if ($value instanceof Collection) {
            return $value;
        }

        return collect($value);
    }
}
