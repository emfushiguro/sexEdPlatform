<?php

namespace App\Services\Monetization;

class RevenueSplitCalculator
{
    /**
     * @return array{gross_amount:float,basis_amount:float,commission_amount:float,instructor_earnings_amount:float}
     */
    public function calculate(
        float $grossAmount,
        float $commissionPercent,
        string $taxBasis = 'gross',
        ?float $netBasisAmount = null,
    ): array {
        $basisAmount = $taxBasis === 'net'
            ? (float) ($netBasisAmount ?? $grossAmount)
            : $grossAmount;

        $commissionAmount = $this->roundCurrency($basisAmount * ($commissionPercent / 100));
        $instructorEarningsAmount = $this->roundCurrency($grossAmount - $commissionAmount);

        return [
            'gross_amount' => $this->roundCurrency($grossAmount),
            'basis_amount' => $this->roundCurrency($basisAmount),
            'commission_amount' => $commissionAmount,
            'instructor_earnings_amount' => $instructorEarningsAmount,
        ];
    }

    private function roundCurrency(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
