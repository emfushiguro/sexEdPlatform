<?php

namespace App\Services\Monetization;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\User;

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
                'payout_status' => 'paid',
                'occurred_at' => $payment->paid_at ?? now(),
            ]
        );
    }

    public function createForCompletedConnectorModulePayment(
        Payment $payment,
        ConnectorModulePurchase $purchase,
        ConnectorModuleEntitlement $entitlement,
    ): ModuleSaleLedger {
        $existing = ModuleSaleLedger::query()
            ->where('payment_id', $payment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $grossAmount = (float) $purchase->gross_amount;
        $sellerType = (string) $purchase->seller_type_snapshot;
        $sellerId = (int) ($purchase->seller_user_id_snapshot ?? 0);
        $isInstructorSale = $sellerType === 'instructor' && $sellerId > 0;

        if ($isInstructorSale) {
            $policy = $this->policyResolver->resolveForInstructor(
                $sellerId,
                $payment->paid_at ?? now(),
            );
            $split = $this->splitCalculator->calculate(
                grossAmount: $grossAmount,
                commissionPercent: (float) $policy->commission_percent,
                taxBasis: (string) $policy->tax_basis,
            );
            $taxBasis = (string) $policy->tax_basis;
            $refundPolicy = (string) $policy->refund_policy;
            $commissionPercent = (float) $policy->commission_percent;
            $sellerNetAmount = $split['instructor_earnings_amount'];
            $sellerName = (string) (User::query()->whereKey($sellerId)->value('name') ?? '');
        } else {
            $split = [
                'gross_amount' => number_format($grossAmount, 2, '.', ''),
                'basis_amount' => number_format($grossAmount, 2, '.', ''),
                'commission_amount' => '0.00',
                'instructor_earnings_amount' => '0.00',
            ];
            $taxBasis = 'gross';
            $refundPolicy = 'disabled';
            $commissionPercent = 0.0;
            $sellerNetAmount = $split['gross_amount'];
            $sellerName = 'Platform';
            $sellerId = null;
        }

        return ModuleSaleLedger::query()->firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'sale_channel' => 'connector_marketplace',
                'connector_module_purchase_id' => $purchase->id,
                'connector_id' => $purchase->connector_id,
                'buyer_user_id' => $purchase->purchased_by,
                'seller_type' => $sellerType,
                'seller_user_id' => $sellerId,
                'seller_name_snapshot' => $sellerName,
                'seat_capacity_snapshot' => $purchase->seat_capacity,
                'module_id' => $purchase->module_id,
                'instructor_id' => $sellerId,
                'learner_id' => null,
                'learner_name_snapshot' => null,
                'currency' => strtoupper((string) $purchase->currency),
                'gross_amount' => $split['gross_amount'],
                'basis_amount' => $split['basis_amount'],
                'commission_percent_snapshot' => $commissionPercent,
                'commission_amount' => $split['commission_amount'],
                'instructor_earnings_amount' => $split['instructor_earnings_amount'],
                'seller_net_amount' => $sellerNetAmount,
                'tax_basis_snapshot' => $taxBasis,
                'refund_policy_snapshot' => $refundPolicy,
                'sale_status' => 'completed',
                'payout_status' => 'paid',
                'occurred_at' => $payment->paid_at ?? now(),
            ],
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
