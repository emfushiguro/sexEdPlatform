<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivityLog;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPayoutStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_transition_payout_status_with_audit_and_invalid_transition_is_blocked(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Payout Module',
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
            'status' => 'completed',
            'transaction_id' => 'PAYOUT-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 199.99,
            'currency' => 'PHP',
            'status' => 'completed',
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
            'commission_percent_snapshot' => 10,
            'commission_amount' => 20,
            'instructor_earnings_amount' => 179.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.monetization.module-revenue.payout.update', $ledger), [
                'payout_status' => 'payable',
            ])
            ->assertRedirect(route('admin.monetization.module-revenue.index'));

        $this->assertDatabaseHas('module_sale_ledgers', [
            'id' => $ledger->id,
            'payout_status' => 'payable',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.monetization.module-revenue.payout.update', $ledger), [
                'payout_status' => 'paid',
                'payout_batch_reference' => 'BATCH-APRIL-01',
            ])
            ->assertRedirect(route('admin.monetization.module-revenue.index'));

        $this->assertDatabaseHas('module_sale_ledgers', [
            'id' => $ledger->id,
            'payout_status' => 'paid',
            'payout_batch_reference' => 'BATCH-APRIL-01',
        ]);

        $this->from(route('admin.monetization.module-revenue.index'))
            ->actingAs($admin)
            ->patch(route('admin.monetization.module-revenue.payout.update', $ledger), [
                'payout_status' => 'pending',
            ])
            ->assertSessionHasErrors('payout_status');

        $this->assertDatabaseHas('module_sale_ledgers', [
            'id' => $ledger->id,
            'payout_status' => 'paid',
        ]);

        $logs = AdminActivityLog::query()
            ->where('action', 'module_sale_ledger.payout_status.transition')
            ->where('entity_type', ModuleSaleLedger::class)
            ->where('entity_id', $ledger->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $logs);
        $this->assertSame('pending', data_get($logs->first()->before_json, 'payout_status'));
        $this->assertSame('payable', data_get($logs->first()->after_json, 'payout_status'));
        $this->assertSame('payable', data_get($logs->last()->before_json, 'payout_status'));
        $this->assertSame('paid', data_get($logs->last()->after_json, 'payout_status'));
    }
}
