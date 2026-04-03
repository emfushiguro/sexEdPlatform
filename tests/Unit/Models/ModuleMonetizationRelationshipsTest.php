<?php

namespace Tests\Unit\Models;

use App\Enums\PaymentStatus;
use App\Models\CommissionPolicy;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleMonetizationRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_module_purchase_module_and_user_relationships_resolve_module_sale_ledger(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'access_type' => 'paid',
            'price_amount' => 199.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 199.99,
            'method' => 'paymongo',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'REL-TEST-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 199.99,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_COMPLETED,
            'purchased_at' => now(),
        ]);

        $ledger = ModuleSaleLedger::query()->create([
            'payment_id' => $payment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'learner_id' => $learner->id,
            'learner_name_snapshot' => $learner->name,
            'currency' => 'PHP',
            'gross_amount' => 199.99,
            'basis_amount' => 199.99,
            'commission_percent_snapshot' => 10.00,
            'commission_amount' => 20.00,
            'instructor_earnings_amount' => 179.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->assertTrue($payment->moduleSaleLedger()->exists());
        $this->assertSame($ledger->id, $payment->moduleSaleLedger?->id);

        $this->assertTrue($purchase->moduleSaleLedger()->exists());
        $this->assertSame($ledger->id, $purchase->moduleSaleLedger?->id);

        $this->assertTrue($module->moduleSaleLedgers()->exists());
        $this->assertSame($ledger->id, $module->moduleSaleLedgers()->first()?->id);

        $this->assertTrue($instructor->instructorSaleLedgers()->exists());
        $this->assertSame($ledger->id, $instructor->instructorSaleLedgers()->first()?->id);

        $this->assertTrue($learner->learnerSaleLedgers()->exists());
        $this->assertSame($ledger->id, $learner->learnerSaleLedgers()->first()?->id);
    }

    public function test_user_has_instructor_override_policy_relation(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        $policy = CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_INSTRUCTOR,
            'scope_id' => $instructor->id,
            'commission_percent' => 8.50,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
            'updated_by' => null,
        ]);

        $this->assertTrue($instructor->instructorOverridePolicies()->exists());
        $this->assertSame($policy->id, $instructor->instructorOverridePolicies()->first()?->id);
    }
}
