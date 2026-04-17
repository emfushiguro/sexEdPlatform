<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleRevision;
use App\Models\Payment;
use App\Models\User;
use App\Services\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSaleLedgerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_module_payment_creates_ledger_row(): void
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

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'amount' => 199.99,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_PENDING,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 199.99,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'LEDGER-CREATE-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
                'module_purchase_id' => $purchase->id,
            ],
        ]);

        config()->set('monetization.default_commission_percent', 10.00);

        app(ModulePurchaseService::class)->completePayment($payment, 'paymongo', 'pay_123');

        $this->assertDatabaseHas('module_sale_ledgers', [
            'payment_id' => $payment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'learner_id' => $learner->id,
            'commission_amount' => 20.00,
            'instructor_earnings_amount' => 179.99,
            'payout_status' => 'paid',
        ]);
    }

    public function test_completed_payment_backfills_instructor_owner_for_legacy_module_and_records_ledger(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $module = Module::factory()->create([
            'created_by' => null,
            'access_type' => 'paid',
            'price_amount' => 149.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => [
                    'created_by' => $instructor->id,
                ],
            ],
            'submitted_by' => $instructor->id,
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'amount' => 149.99,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_PENDING,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 149.99,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'LEDGER-LEGACY-OWNER-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
                'module_purchase_id' => $purchase->id,
            ],
        ]);

        config()->set('monetization.default_commission_percent', 10.00);

        app(ModulePurchaseService::class)->completePayment($payment, 'paymongo', 'pay_legacy');

        $module->refresh();

        $this->assertSame($instructor->id, (int) $module->created_by);

        $this->assertDatabaseHas('module_sale_ledgers', [
            'payment_id' => $payment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'learner_id' => $learner->id,
            'commission_amount' => 15.00,
            'instructor_earnings_amount' => 134.99,
        ]);
    }
}
