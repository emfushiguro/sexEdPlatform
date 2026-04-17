<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\Refund;
use App\Support\Finance\FinancialReportFilter;
use Carbon\CarbonImmutable;

class FinancialTrendDatasetBuilder
{
    /**
     * @return array{labels: array<int, string>, gross: array<int, float>, net: array<int, float>}
     */
    public function build(FinancialReportFilter $filter): array
    {
        $payments = Payment::query()
            ->where('status', 'completed')
            ->whereNull('archived_at')
            ->whereBetween('paid_at', [$filter->utcStart, $filter->utcEnd])
            ->get(['paid_at', 'amount']);

        $refunds = Refund::query()
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$filter->utcStart, $filter->utcEnd])
            ->get(['processed_at', 'amount']);

        $grossByBucket = [];
        foreach ($payments as $payment) {
            if (!$payment->paid_at) {
                continue;
            }

            $bucket = $this->bucketKey(CarbonImmutable::parse($payment->paid_at)->setTimezone($filter->timezone), $filter->granularity);
            $grossByBucket[$bucket] = ($grossByBucket[$bucket] ?? 0.0) + (float) $payment->amount;
        }

        $refundByBucket = [];
        foreach ($refunds as $refund) {
            if (!$refund->processed_at) {
                continue;
            }

            $bucket = $this->bucketKey(CarbonImmutable::parse($refund->processed_at)->setTimezone($filter->timezone), $filter->granularity);
            $refundByBucket[$bucket] = ($refundByBucket[$bucket] ?? 0.0) + (float) $refund->amount;
        }

        $buckets = $this->buildBucketSequence($filter);
        $labels = [];
        $grossSeries = [];
        $netSeries = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $gross = (float) ($grossByBucket[$bucket['key']] ?? 0.0);
            $refund = (float) ($refundByBucket[$bucket['key']] ?? 0.0);

            $grossSeries[] = round($gross, 2);
            $netSeries[] = round($gross - $refund, 2);
        }

        return [
            'labels' => $labels,
            'gross' => $grossSeries,
            'net' => $netSeries,
        ];
    }

    private function bucketKey(CarbonImmutable $value, string $granularity): string
    {
        return match ($granularity) {
            'day' => $value->format('Y-m-d'),
            'week' => $value->startOfWeek()->format('Y-m-d'),
            default => $value->startOfMonth()->format('Y-m'),
        };
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function buildBucketSequence(FinancialReportFilter $filter): array
    {
        $sequence = [];

        if ($filter->granularity === 'day') {
            $cursor = $filter->localStart->startOfDay();
            while ($cursor->lessThanOrEqualTo($filter->localEnd)) {
                $sequence[] = [
                    'key' => $cursor->format('Y-m-d'),
                    'label' => $cursor->format('M d'),
                ];
                $cursor = $cursor->addDay();
            }

            return $sequence;
        }

        if ($filter->granularity === 'week') {
            $cursor = $filter->localStart->startOfWeek();
            while ($cursor->lessThanOrEqualTo($filter->localEnd)) {
                $sequence[] = [
                    'key' => $cursor->format('Y-m-d'),
                    'label' => $cursor->format('M d'),
                ];
                $cursor = $cursor->addWeek();
            }

            return $sequence;
        }

        $cursor = $filter->localStart->startOfMonth();
        while ($cursor->lessThanOrEqualTo($filter->localEnd)) {
            $sequence[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
            ];
            $cursor = $cursor->addMonth();
        }

        return $sequence;
    }
}
