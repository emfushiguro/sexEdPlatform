<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleRevenueInstructorRollupViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dedicated_instructor_rollup_page(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Rollup Instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'title' => 'Rollup Module',
            'created_by' => $instructor->id,
            'access_type' => 'paid',
            'price_amount' => 159.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 159.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'ROLLUP-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 159.99,
            'currency' => 'PHP',
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        ModuleSaleLedger::query()->create([
            'payment_id' => $payment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'learner_id' => $learner->id,
            'learner_name_snapshot' => $learner->name,
            'currency' => 'PHP',
            'gross_amount' => 159.99,
            'basis_amount' => 159.99,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 16,
            'instructor_earnings_amount' => 143.99,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.instructors.show', $instructor))
            ->assertOk()
            ->assertSee('Instructor Revenue Details', false)
            ->assertSee('Rollup Instructor', false)
            ->assertSee('Rollup Module', false)
            ->assertSee('Transactions', false)
            ->assertSee('View transaction details', false);
    }
}
