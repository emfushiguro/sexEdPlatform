<?php

namespace App\Services\Monetization;

use App\Models\CommissionPolicy;
use Carbon\CarbonInterface;

class CommissionPolicyResolver
{
    public function resolveForInstructor(int $instructorId, ?CarbonInterface $at = null): CommissionPolicy
    {
        $at = $at ?? now();

        $override = CommissionPolicy::query()
            ->active()
            ->effectiveAt($at)
            ->instructor($instructorId)
            ->latest('effective_from')
            ->latest('id')
            ->first();

        if ($override) {
            return $override;
        }

        $global = CommissionPolicy::query()
            ->active()
            ->effectiveAt($at)
            ->global()
            ->latest('effective_from')
            ->latest('id')
            ->first();

        if ($global) {
            return $global;
        }

        return $this->buildFallbackPolicy($at);
    }

    private function buildFallbackPolicy(CarbonInterface $at): CommissionPolicy
    {
        $commissionPercent = (float) config('monetization.default_commission_percent', 10.00);
        $taxBasis = (string) config('monetization.default_tax_basis', 'gross');
        $refundPolicy = (string) config('monetization.default_refund_policy', 'disabled');

        return new CommissionPolicy([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => $commissionPercent,
            'tax_basis' => $taxBasis,
            'refund_policy' => $refundPolicy,
            'is_active' => true,
            'effective_from' => $at,
            'effective_to' => null,
            'updated_by' => null,
        ]);
    }
}
