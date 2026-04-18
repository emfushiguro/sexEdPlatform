<?php

namespace App\Services\Finance;

use App\Support\Finance\FinancialReportFilter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class FinancialReportFilterNormalizer
{
    public function normalize(array $filters, ?int $forcedInstructorId = null): FinancialReportFilter
    {
        $reportType = (string) ($filters['report_type'] ?? 'monthly');
        $timezone = (string) ($filters['timezone'] ?? 'Asia/Manila');
        $now = CarbonImmutable::now($timezone);

        [$localStart, $localEnd] = match ($reportType) {
            'weekly' => [$now->startOfWeek(CarbonInterface::MONDAY)->startOfDay(), $now->endOfWeek(CarbonInterface::SUNDAY)->endOfDay()],
            'monthly' => [$now->startOfMonth()->startOfDay(), $now->endOfMonth()->endOfDay()],
            'yearly' => [$now->startOfYear()->startOfDay(), $now->endOfYear()->endOfDay()],
            'custom' => $this->resolveCustomRange($filters, $timezone),
            default => throw ValidationException::withMessages([
                'report_type' => 'Unsupported report type supplied.',
            ]),
        };

        $instructorId = $forcedInstructorId ?? (isset($filters['instructor_id']) ? (int) $filters['instructor_id'] : null);
        $moduleId = isset($filters['module_id']) ? (int) $filters['module_id'] : null;

        return new FinancialReportFilter(
            reportType: $reportType,
            timezone: $timezone,
            localStart: $localStart,
            localEnd: $localEnd,
            utcStart: $localStart->setTimezone('UTC'),
            utcEnd: $localEnd->setTimezone('UTC'),
            granularity: $this->resolveGranularity($reportType, $localStart, $localEnd),
            instructorId: $instructorId,
            moduleId: $moduleId,
        );
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveCustomRange(array $filters, string $timezone): array
    {
        $dateFrom = (string) ($filters['date_from'] ?? '');
        $dateTo = (string) ($filters['date_to'] ?? '');

        if ($dateFrom === '' || $dateTo === '') {
            throw ValidationException::withMessages([
                'date_from' => 'Custom report requires date_from and date_to.',
                'date_to' => 'Custom report requires date_from and date_to.',
            ]);
        }

        $localStart = CarbonImmutable::parse($dateFrom, $timezone)->startOfDay();
        $localEnd = CarbonImmutable::parse($dateTo, $timezone)->endOfDay();

        if ($localEnd->lessThan($localStart)) {
            throw ValidationException::withMessages([
                'date_to' => 'date_to must be greater than or equal to date_from.',
            ]);
        }

        $maxRangeDays = (int) config('monetization.reporting.max_custom_range_days', 365);
        $inclusiveDays = $localStart->diffInDays($localEnd) + 1;
        if ($inclusiveDays > $maxRangeDays) {
            throw ValidationException::withMessages([
                'date_to' => 'Custom date range exceeds allowed maximum of ' . $maxRangeDays . ' days.',
            ]);
        }

        return [$localStart, $localEnd];
    }

    private function resolveGranularity(string $reportType, CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($reportType === 'weekly') {
            return 'day';
        }

        if ($reportType === 'monthly') {
            return 'week';
        }

        if ($reportType === 'yearly') {
            return 'month';
        }

        $days = $start->diffInDays($end) + 1;

        return match (true) {
            $days <= 14 => 'day',
            $days <= 120 => 'week',
            default => 'month',
        };
    }
}
