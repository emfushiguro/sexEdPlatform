<?php

namespace App\Services\Finance;

use App\Models\InstructorEarningsVisibility;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\Refund;
use App\Support\Finance\FinancialReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinancialReportService
{
    public function __construct(
        private readonly FinancialReportFilterNormalizer $filterNormalizer,
        private readonly FinancialTrendDatasetBuilder $trendDatasetBuilder,
    ) {
    }

    public function getSummary(array|FinancialReportFilter $filters): array
    {
        $filter = $this->resolveFilter($filters);

        $paymentsQuery = Payment::query()
            ->where('status', 'completed')
            ->whereNull('archived_at')
            ->whereBetween('paid_at', [$filter->utcStart, $filter->utcEnd]);

        $grossRevenue = (float) (clone $paymentsQuery)->sum('amount');

        $refundAmount = (float) Refund::query()
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$filter->utcStart, $filter->utcEnd])
            ->sum('amount');

        $subscriptionRevenue = (float) (clone $paymentsQuery)
            ->where('payment_details->payment_scope', 'subscription')
            ->sum('amount');

        $moduleRevenueQuery = ModuleSaleLedger::query()
            ->where('sale_status', '!=', 'archived')
            ->whereBetween('occurred_at', [$filter->utcStart, $filter->utcEnd]);

        if ($filter->instructorId !== null) {
            $moduleRevenueQuery->where('instructor_id', $filter->instructorId);
            $moduleRevenueQuery->whereDoesntHave('visibility', function ($visibilityQuery) use ($filter) {
                $visibilityQuery
                    ->where('instructor_id', $filter->instructorId)
                    ->whereNotNull('deleted_at');
            });
        }

        if ($filter->moduleId !== null) {
            $moduleRevenueQuery->where('module_id', $filter->moduleId);
        }

        $moduleRevenue = (float) (clone $moduleRevenueQuery)->sum('gross_amount');
        $platformEarnings = (float) (clone $moduleRevenueQuery)->sum('commission_amount');
        $instructorEarnings = (float) (clone $moduleRevenueQuery)->sum('instructor_earnings_amount');

        return [
            'filter' => $filter->toArray(),
            'summary' => [
                'total_revenue' => round($grossRevenue, 2),
                'gross_revenue' => round($grossRevenue, 2),
                'net_revenue' => round($grossRevenue - $refundAmount, 2),
                'refund_amount' => round($refundAmount, 2),
                'subscription_revenue' => round($subscriptionRevenue, 2),
                'module_revenue' => round($moduleRevenue, 2),
                'platform_earnings' => round($platformEarnings, 2),
                'instructor_earnings' => round($instructorEarnings, 2),
            ],
            'trend' => $this->trendDatasetBuilder->build($filter),
        ];
    }

    public function getRevenueBreakdown(array|FinancialReportFilter $filters): array
    {
        $filter = $this->resolveFilter($filters);

        $scopedPayments = Payment::query()
            ->where('status', 'completed')
            ->whereNull('archived_at')
            ->whereBetween('paid_at', [$filter->utcStart, $filter->utcEnd])
            ->get(['amount', 'payment_details']);

        $sourceMap = [];
        foreach ($scopedPayments as $payment) {
            $scope = (string) data_get($payment->payment_details, 'payment_scope', 'other');
            if ($scope === '') {
                $scope = 'other';
            }

            $sourceMap[$scope] = ($sourceMap[$scope] ?? 0.0) + (float) $payment->amount;
        }

        ksort($sourceMap);

        $ledgerQuery = ModuleSaleLedger::query()
            ->where('sale_status', '!=', 'archived')
            ->whereBetween('occurred_at', [$filter->utcStart, $filter->utcEnd]);

        if ($filter->instructorId !== null) {
            $ledgerQuery->where('instructor_id', $filter->instructorId);
            $ledgerQuery->whereDoesntHave('visibility', function ($visibilityQuery) use ($filter) {
                $visibilityQuery
                    ->where('instructor_id', $filter->instructorId)
                    ->whereNotNull('deleted_at');
            });
        }

        if ($filter->moduleId !== null) {
            $ledgerQuery->where('module_id', $filter->moduleId);
        }

        $topInstructors = (clone $ledgerQuery)
            ->selectRaw('instructor_id, COUNT(*) as sales_count, SUM(gross_amount) as gross_amount, SUM(commission_amount) as commission_amount, SUM(instructor_earnings_amount) as instructor_earnings_amount')
            ->with('instructor:id,name,email')
            ->groupBy('instructor_id')
            ->orderByDesc('gross_amount')
            ->limit(10)
            ->get();

        $topModules = (clone $ledgerQuery)
            ->selectRaw('module_id, COUNT(*) as sales_count, SUM(gross_amount) as gross_amount, SUM(commission_amount) as commission_amount, SUM(instructor_earnings_amount) as instructor_earnings_amount')
            ->with('module:id,title')
            ->groupBy('module_id')
            ->orderByDesc('gross_amount')
            ->limit(10)
            ->get();

        return [
            'source_breakdown' => collect($sourceMap)
                ->map(fn (float $amount, string $source) => [
                    'source' => $source,
                    'amount' => round($amount, 2),
                ])
                ->values()
                ->all(),
            'top_instructors' => $topInstructors,
            'top_modules' => $topModules,
        ];
    }

    public function getInstructorEarnings(array|FinancialReportFilter $filters): array
    {
        $filter = $this->resolveFilter($filters);

        if ($filter->instructorId === null) {
            return [
                'filter' => $filter->toArray(),
                'summary' => [
                    'total_transactions' => 0,
                    'gross_revenue' => 0.0,
                    'platform_commission' => 0.0,
                    'instructor_earnings' => 0.0,
                ],
                'module_breakdown' => [],
            ];
        }

        $baseQuery = ModuleSaleLedger::query()
            ->where('sale_status', '!=', 'archived')
            ->where('instructor_id', $filter->instructorId)
            ->whereBetween('occurred_at', [$filter->utcStart, $filter->utcEnd])
            ->whereDoesntHave('visibility', function ($visibilityQuery) use ($filter) {
                $visibilityQuery
                    ->where('instructor_id', $filter->instructorId)
                    ->whereNotNull('deleted_at');
            });

        if ($filter->moduleId !== null) {
            $baseQuery->where('module_id', $filter->moduleId);
        }

        $moduleBreakdown = (clone $baseQuery)
            ->selectRaw('module_id, COUNT(*) as sales_count, SUM(gross_amount) as gross_amount, SUM(commission_amount) as commission_amount, SUM(instructor_earnings_amount) as instructor_earnings_amount')
            ->with('module:id,title')
            ->groupBy('module_id')
            ->orderByDesc('gross_amount')
            ->get();

        return [
            'filter' => $filter->toArray(),
            'summary' => [
                'total_transactions' => (int) (clone $baseQuery)->count(),
                'gross_revenue' => round((float) (clone $baseQuery)->sum('gross_amount'), 2),
                'platform_commission' => round((float) (clone $baseQuery)->sum('commission_amount'), 2),
                'instructor_earnings' => round((float) (clone $baseQuery)->sum('instructor_earnings_amount'), 2),
            ],
            'module_breakdown' => $moduleBreakdown,
        ];
    }

    public function getInstructorVisibleTransactions(array|FinancialReportFilter $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filter = $this->resolveFilter($filters);

        $query = ModuleSaleLedger::query()
            ->where('sale_status', '!=', 'archived')
            ->where('instructor_id', $filter->instructorId)
            ->whereBetween('occurred_at', [$filter->utcStart, $filter->utcEnd])
            ->with([
                'module:id,title,thumbnail',
                'learner:id,name,first_name,last_name,email',
                'learner.learnerProfile:id,user_id,avatar_path',
                'payment:id,transaction_id,method,status,paid_at',
                'modulePurchase:id,module_id,purchased_at,status',
            ])
            ->whereDoesntHave('visibility', function ($visibilityQuery) use ($filter) {
                $visibilityQuery
                    ->where('instructor_id', $filter->instructorId)
                    ->whereNotNull('deleted_at');
            });

        if ($filter->moduleId !== null) {
            $query->where('module_id', $filter->moduleId);
        }

        return $query
            ->latest('occurred_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getHiddenTransactionIdsForInstructor(array|FinancialReportFilter $filters): array
    {
        $filter = $this->resolveFilter($filters);

        if ($filter->instructorId === null) {
            return [];
        }

        return InstructorEarningsVisibility::query()
            ->where('instructor_id', $filter->instructorId)
            ->whereNotNull('deleted_at')
            ->pluck('module_sale_ledger_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function resolveFilter(array|FinancialReportFilter $filters): FinancialReportFilter
    {
        if ($filters instanceof FinancialReportFilter) {
            return $filters;
        }

        return $this->filterNormalizer->normalize($filters);
    }
}
