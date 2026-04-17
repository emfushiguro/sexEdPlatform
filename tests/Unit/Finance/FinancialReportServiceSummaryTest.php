<?php

namespace Tests\Unit\Finance;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Finance\FinancialReportFilterNormalizer;
use App\Services\Finance\FinancialReportService;
use Tests\TestCase;

class FinancialReportServiceSummaryTest extends TestCase
{
    public function test_summary_returns_expected_financial_metrics(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'access_type' => 'paid',
            'is_published' => true,
            'price_amount' => 500,
            'price_currency' => 'PHP',
        ]);

        $subscriptionPayment = Payment::query()->create([
            'user_id' => $learner->id,
            'amount' => 300,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'SUB-100',
            'payment_details' => ['payment_scope' => 'subscription'],
            'paid_at' => now()->subDay(),
        ]);

        $modulePayment = Payment::query()->create([
            'user_id' => $learner->id,
            'amount' => 500,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'MOD-200',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $modulePayment->id,
            'amount' => 500,
            'currency' => 'PHP',
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        ModuleSaleLedger::query()->create([
            'payment_id' => $modulePayment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'learner_id' => $learner->id,
            'learner_name_snapshot' => $learner->name,
            'currency' => 'PHP',
            'gross_amount' => 500,
            'basis_amount' => 500,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 50,
            'instructor_earnings_amount' => 450,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'paid',
            'occurred_at' => now(),
        ]);

        Refund::query()->create([
            'payment_id' => $subscriptionPayment->id,
            'user_id' => $learner->id,
            'processed_by' => null,
            'amount' => 100,
            'status' => 'completed',
            'refund_id' => 'RF-100',
            'reason' => 'test',
            'processed_at' => now(),
        ]);

        $service = app(FinancialReportService::class);
        $normalizer = app(FinancialReportFilterNormalizer::class);

        $filter = $normalizer->normalize(['report_type' => 'monthly']);
        $payload = $service->getSummary($filter);

        $this->assertSame(800.0, (float) data_get($payload, 'summary.total_revenue'));
        $this->assertSame(700.0, (float) data_get($payload, 'summary.net_revenue'));
        $this->assertSame(300.0, (float) data_get($payload, 'summary.subscription_revenue'));
        $this->assertSame(500.0, (float) data_get($payload, 'summary.module_revenue'));
        $this->assertSame(50.0, (float) data_get($payload, 'summary.platform_earnings'));
        $this->assertSame(450.0, (float) data_get($payload, 'summary.instructor_earnings'));
    }
}
