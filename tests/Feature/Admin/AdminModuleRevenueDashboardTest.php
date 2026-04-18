<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleRevenueDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_revenue_dashboard_with_kpis_tables_and_filters(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructorA = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor A']);
        $instructorA->assignRole('instructor');
        $instructorB = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor B']);
        $instructorB->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $moduleA = Module::factory()->create([
            'title' => 'Module Alpha',
            'created_by' => $instructorA->id,
            'is_published' => true,
            'access_type' => 'paid',
            'price_amount' => 199.99,
            'price_currency' => 'PHP',
        ]);

        $moduleB = Module::factory()->create([
            'title' => 'Module Beta',
            'created_by' => $instructorB->id,
            'is_published' => true,
            'access_type' => 'paid',
            'price_amount' => 299.99,
            'price_currency' => 'PHP',
        ]);

        $paymentA = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 199.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'REV-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $moduleA->id],
            'paid_at' => now()->subDay(),
        ]);

        $paymentB = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'REV-2',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $moduleB->id],
            'paid_at' => now(),
        ]);

        $purchaseA = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $moduleA->id,
            'payment_id' => $paymentA->id,
            'amount' => 199.99,
            'currency' => 'PHP',
            'status' => 'completed',
            'purchased_at' => now()->subDay(),
        ]);

        $purchaseB = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $moduleB->id,
            'payment_id' => $paymentB->id,
            'amount' => 299.99,
            'currency' => 'PHP',
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        ModuleSaleLedger::query()->create([
            'payment_id' => $paymentA->id,
            'module_purchase_id' => $purchaseA->id,
            'module_id' => $moduleA->id,
            'instructor_id' => $instructorA->id,
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
            'payout_status' => 'paid',
            'occurred_at' => now()->subDay(),
        ]);

        ModuleSaleLedger::query()->create([
            'payment_id' => $paymentB->id,
            'module_purchase_id' => $purchaseB->id,
            'module_id' => $moduleB->id,
            'instructor_id' => $instructorB->id,
            'learner_id' => $learner->id,
            'learner_name_snapshot' => $learner->name,
            'currency' => 'PHP',
            'gross_amount' => 299.99,
            'basis_amount' => 299.99,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 30,
            'instructor_earnings_amount' => 269.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.index'))
            ->assertOk()
            ->assertSee('Module Revenue Dashboard', false)
            ->assertSee('Total Transactions', false)
            ->assertSee('Module Alpha', false)
            ->assertSee('Module Beta', false)
            ->assertSee('Instructor A', false)
            ->assertSee('Instructor B', false)
            ->assertSee('View transaction details', false)
            ->assertDontSee('Mark as payable', false)
            ->assertDontSee('Mark as paid', false);

        $filteredResponse = $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.index', [
                'instructor_id' => $instructorA->id,
                'module_id' => $moduleA->id,
                'payout_status' => 'paid',
            ]))
            ->assertOk()
            ->assertSee('Module Alpha', false)
            ->assertSee('Paid', false);

        $filteredHtml = $filteredResponse->getContent();

        $this->assertStringContainsString('Module Alpha', $filteredHtml);
        $this->assertStringContainsString('REV-1', $filteredHtml);
        $this->assertStringNotContainsString('REV-2', $filteredHtml);
    }
}
