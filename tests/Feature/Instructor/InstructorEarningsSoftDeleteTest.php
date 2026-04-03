<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorEarningsSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_hide_ledger_row_without_affecting_admin_reporting(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner', 'name' => 'Learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Soft Delete Module',
            'access_type' => 'paid',
            'price_amount' => 149.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 149.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'HIDE-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 149.99,
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
            'gross_amount' => 149.99,
            'basis_amount' => 149.99,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 15,
            'instructor_earnings_amount' => 134.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->delete(route('instructor.earnings.visibility.destroy', $ledger), [
                'delete_reason' => 'Already settled offline',
            ])
            ->assertRedirect(route('instructor.earnings.index'));

        $this->assertDatabaseHas('instructor_earnings_visibility', [
            'module_sale_ledger_id' => $ledger->id,
            'instructor_id' => $instructor->id,
            'deleted_by' => $instructor->id,
            'delete_reason' => 'Already settled offline',
        ]);

        $indexResponse = $this->actingAs($instructor)
            ->get(route('instructor.earnings.index'))
            ->assertOk();

        $this->assertStringNotContainsString('Soft Delete Module</td>', $indexResponse->getContent());

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.index'))
            ->assertOk()
            ->assertSee('Soft Delete Module', false);
    }
}
