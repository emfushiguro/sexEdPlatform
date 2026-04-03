<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\CommissionPolicy;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSaleLedgerIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_completion_events_create_only_one_ledger_row(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'access_type' => 'paid',
            'price_amount' => 299.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'amount' => 299.99,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_PENDING,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299.99,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'LEDGER-IDEMPOTENCY-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
                'module_purchase_id' => $purchase->id,
            ],
        ]);

        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => 10.00,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
        ]);

        $service = app(ModulePurchaseService::class);
        $service->completePayment($payment, 'paymongo', 'pay_dup_1');
        $service->completePayment($payment->fresh(), 'paymongo', 'pay_dup_1');

        $this->assertSame(1, (int) \DB::table('module_sale_ledgers')->where('payment_id', $payment->id)->count());
    }
}
