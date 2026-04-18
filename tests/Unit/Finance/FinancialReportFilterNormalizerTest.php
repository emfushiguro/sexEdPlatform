<?php

namespace Tests\Unit\Finance;

use App\Services\Finance\FinancialReportFilterNormalizer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialReportFilterNormalizerTest extends TestCase
{
    public function test_monthly_filter_defaults_to_asia_manila_and_week_granularity(): void
    {
        $normalizer = app(FinancialReportFilterNormalizer::class);

        $filter = $normalizer->normalize([
            'report_type' => 'monthly',
        ]);

        $this->assertSame('Asia/Manila', $filter->timezone);
        $this->assertSame('monthly', $filter->reportType);
        $this->assertSame('week', $filter->granularity);
    }

    public function test_custom_filter_rejects_invalid_date_range(): void
    {
        $normalizer = app(FinancialReportFilterNormalizer::class);

        $this->expectException(ValidationException::class);

        $normalizer->normalize([
            'report_type' => 'custom',
            'date_from' => '2026-04-20',
            'date_to' => '2026-04-19',
        ]);
    }
}
