<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleRevenueTransactionDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_module_revenue_transaction_details_page(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Revenue Instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner', 'name' => 'Revenue Learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'title' => 'Revenue Transaction Module',
            'created_by' => $instructor->id,
            'access_type' => 'paid',
            'price_amount' => 249.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 249.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'REV-DETAIL-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 249.99,
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
            'gross_amount' => 249.99,
            'basis_amount' => 249.99,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 25,
            'instructor_earnings_amount' => 224.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.transactions.show', $ledger))
            ->assertOk()
            ->assertSee('Module Revenue Transaction Details', false)
            ->assertSee('Revenue Transaction Module', false)
            ->assertSee('REV-DETAIL-1', false)
            ->assertSee('Revenue Instructor', false)
            ->assertSee('Revenue Learner', false)
            ->assertSee('Payout Status', false)
            ->assertSee('Paid', false);
    }
}
