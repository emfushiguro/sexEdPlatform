<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminModuleRevenueFinalizedStateMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_sale_ledger_backfill_sets_non_paid_rows_to_paid(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

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
            'status' => 'completed',
            'transaction_id' => 'MIG-REV-1',
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

        $this->assertSame('pending', $ledger->fresh()->payout_status);

        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_04_14_000100_backfill_module_sale_ledger_paid_status.php', '--force' => true]);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_04_14_000100_backfill_module_sale_ledger_paid_status.php', '--force' => true]);

        $this->assertSame('paid', $ledger->fresh()->payout_status);
    }
}
