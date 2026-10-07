<?php

namespace App\Services;

use App\Models\Payment;

class PaymentReceiptService
{
    /**
     * @return array<string, mixed>
     */
    public function resolveViewData(Payment $payment): array
    {
        $payment = $this->loadReceiptRelations($payment);

        $details = (array) ($payment->payment_details ?? []);
        $modulePurchase = $payment->modulePurchase ?? $payment->moduleSaleLedger?->modulePurchase;
        $connectorModulePurchase = $payment->connectorModulePurchase
            ?? $payment->moduleSaleLedger?->connectorModulePurchase;
        $module = $modulePurchase?->module ?? $payment->moduleSaleLedger?->module;
        $module ??= $connectorModulePurchase?->module;
        $moduleInstructor = $module?->creator ?? $payment->moduleSaleLedger?->instructor;
        $isConnectorModulePurchase = $payment->isConnectorModulePurchase();
        $isModulePurchase = (string) data_get($details, 'payment_scope') === 'module_purchase'
            || $modulePurchase !== null
            || $payment->isModuleCommercePurchase();

        return [
            'payment' => $payment,
            'details' => $details,
            'subscription' => $payment->subscription,
            'modulePurchase' => $modulePurchase,
            'connectorModulePurchase' => $connectorModulePurchase,
            'connector' => $connectorModulePurchase?->connector,
            'isConnectorModulePurchase' => $isConnectorModulePurchase,
            'seller' => $payment->moduleSaleLedger?->seller,
            'module' => $module,
            'moduleInstructor' => $moduleInstructor,
            'isModulePurchase' => $isModulePurchase,
            'reference' => $this->resolveReference($payment, $details),
            'method' => strtoupper((string) ($payment->method ?? data_get($details, 'payment_method', 'N/A'))),
            'datePaid' => $payment->paid_at ?? $payment->created_at,
        ];
    }

    public function loadReceiptRelations(Payment $payment): Payment
    {
        $payment->loadMissing([
            'user.profile',
            'user.learnerProfile.city',
            'user.learnerProfile.barangay',
            'subscription.plan',
            'subscription.planPrice',
            'modulePurchase.module.creator',
            'connectorModulePurchase.connector',
            'connectorModulePurchase.module.creator',
            'connectorModulePurchase.listing',
            'connectorModulePurchase.entitlement',
            'moduleSaleLedger.module.creator',
            'moduleSaleLedger.instructor',
            'moduleSaleLedger.learner',
            'moduleSaleLedger.connectorModulePurchase.connector',
            'moduleSaleLedger.buyer',
            'moduleSaleLedger.seller',
            'invoice',
        ]);

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function resolveReference(Payment $payment, array $details): string
    {
        $paymongoRef = data_get($details, 'paymongo_payment_id')
            ?? data_get($details, 'paymongo_link_id');

        if (!empty($payment->transaction_id)) {
            return (string) $payment->transaction_id;
        }

        if (!empty($paymongoRef)) {
            return strtoupper(substr((string) $paymongoRef, -10));
        }

        return (string) $payment->id;
    }
}
