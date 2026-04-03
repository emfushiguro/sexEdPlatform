<?php

namespace Tests\Unit\Services;

use App\Services\Monetization\RevenueSplitCalculator;
use Tests\TestCase;

class RevenueSplitCalculatorTest extends TestCase
{
    public function test_calculate_uses_half_up_rounding_for_commission(): void
    {
        $result = app(RevenueSplitCalculator::class)->calculate(
            grossAmount: 100.05,
            commissionPercent: 10.00,
            taxBasis: 'gross'
        );

        $this->assertSame(100.05, $result['gross_amount']);
        $this->assertSame(100.05, $result['basis_amount']);
        $this->assertSame(10.01, $result['commission_amount']);
        $this->assertSame(90.04, $result['instructor_earnings_amount']);
    }

    public function test_calculate_supports_net_tax_basis_math(): void
    {
        $result = app(RevenueSplitCalculator::class)->calculate(
            grossAmount: 112.00,
            commissionPercent: 10.00,
            taxBasis: 'net',
            netBasisAmount: 100.00
        );

        $this->assertSame(112.00, $result['gross_amount']);
        $this->assertSame(100.00, $result['basis_amount']);
        $this->assertSame(10.00, $result['commission_amount']);
        $this->assertSame(102.00, $result['instructor_earnings_amount']);
    }
}
