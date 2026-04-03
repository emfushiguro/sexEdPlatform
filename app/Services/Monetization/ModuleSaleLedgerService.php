<?php

namespace App\Services\Monetization;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;

class ModuleSaleLedgerService
{
    public function __construct(
        private readonly CommissionPolicyResolver $policyResolver,
        private readonly RevenueSplitCalculator $splitCalculator,
    ) {
    }

    public function createForCompletedModulePayment(Payment $payment): ?ModuleSaleLedger
    {
        if ((string) data_get($payment->payment_details, 'payment_scope') !== 'module_purchase') {
            return null;
        }

        if ($payment->status !== PaymentStatus::Completed) {
            return null;
        }

        $existing = ModuleSaleLedger::query()
            ->where('payment_id', $payment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $modulePurchase = $this->resolveModulePurchase($payment);
        $module = $this->resolveModule($payment, $modulePurchase);

        $instructorId = $module ? $this->resolveInstructorId($module) : null;

        if (!$module || !$modulePurchase || !$instructorId) {
            return null;
        }

        $policy = $this->policyResolver->resolveForInstructor($instructorId, $payment->paid_at ?? now());

        $split = $this->splitCalculator->calculate(
            grossAmount: (float) $payment->amount,
            commissionPercent: (float) $policy->commission_percent,
            taxBasis: (string) $policy->tax_basis,
        );

        return ModuleSaleLedger::query()->firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'module_purchase_id' => $modulePurchase->id,
                'module_id' => $module->id,
                'instructor_id' => $instructorId,
                'learner_id' => (int) $payment->user_id,
                'learner_name_snapshot' => (string) ($payment->user?->name ?? ''),
                'currency' => strtoupper((string) ($modulePurchase->currency ?? 'PHP')),
                'gross_amount' => $split['gross_amount'],
                'basis_amount' => $split['basis_amount'],
                'commission_percent_snapshot' => (float) $policy->commission_percent,
                'commission_amount' => $split['commission_amount'],
                'instructor_earnings_amount' => $split['instructor_earnings_amount'],
                'tax_basis_snapshot' => (string) $policy->tax_basis,
                'refund_policy_snapshot' => (string) $policy->refund_policy,
                'sale_status' => 'completed',
                'payout_status' => 'pending',
                'occurred_at' => $payment->paid_at ?? now(),
            ]
        );
    }

    private function resolveModulePurchase(Payment $payment): ?ModulePurchase
    {
        $purchaseId = (int) data_get($payment->payment_details, 'module_purchase_id', 0);

        if ($purchaseId > 0) {
            $purchase = ModulePurchase::query()->find($purchaseId);
            if ($purchase) {
                return $purchase;
            }
        }

        return ModulePurchase::query()
            ->where('payment_id', $payment->id)
            ->latest('id')
            ->first();
    }

    private function resolveModule(Payment $payment, ?ModulePurchase $modulePurchase): ?Module
    {
        $moduleId = (int) data_get($payment->payment_details, 'module_id', 0);

        if ($moduleId <= 0 && $modulePurchase) {
            $moduleId = (int) $modulePurchase->module_id;
        }

        if ($moduleId <= 0) {
            return null;
        }

        return Module::query()->find($moduleId);
    }

    private function resolveInstructorId(Module $module): ?int
    {
        $instructorId = (int) ($module->created_by ?? 0);

        if ($instructorId <= 0) {
            $instructorId = (int) data_get($module->publishedSnapshot(), 'module.created_by', 0);
        }

        if ($instructorId <= 0 && $module->published_revision_id) {
            $instructorId = (int) ModuleRevision::query()
                ->whereKey((int) $module->published_revision_id)
                ->value('submitted_by');
        }

        if ($instructorId <= 0) {
            $instructorId = (int) ModuleReviewRequest::query()
                ->where('module_id', $module->id)
                ->whereNotNull('submitted_by')
                ->latest('id')
                ->value('submitted_by');
        }

        if ($instructorId <= 0) {
            $instructorId = (int) ModuleRevision::query()
                ->where('module_id', $module->id)
                ->whereNotNull('submitted_by')
                ->latest('id')
                ->value('submitted_by');
        }

        if ($instructorId > 0 && (int) ($module->created_by ?? 0) !== $instructorId) {
            $module->forceFill(['created_by' => $instructorId])->saveQuietly();
        }

        return $instructorId > 0 ? $instructorId : null;
    }
}
