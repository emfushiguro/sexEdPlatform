<?php

namespace Tests\Feature\Instructor;

use App\Models\CommissionPolicy;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorCommissionTransparencyNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_and_earnings_pages_show_commission_transparency_notice(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => 12,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDays(10),
            'effective_to' => null,
            'updated_by' => $admin->id,
        ]);

        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_INSTRUCTOR,
            'scope_id' => $instructor->id,
            'commission_percent' => 18,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'updated_by' => $admin->id,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Transparency Module',
            'access_type' => 'paid',
            'price_amount' => 250,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 250,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'TRANS-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 250,
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
            'gross_amount' => 250,
            'basis_amount' => 250,
            'commission_percent_snapshot' => 18,
            'commission_amount' => 45,
            'instructor_earnings_amount' => 205,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.create'))
            ->assertOk()
            ->assertSee('Platform commission currently applied to your paid modules: 18.00%', false)
            ->assertSee('Estimated net earnings per sale: Price - (Price x 18.00%).', false);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.edit', $module))
            ->assertOk()
            ->assertSee('Platform commission currently applied to your paid modules: 18.00%', false)
            ->assertSee('Estimated net earnings per sale: Price - (Price x 18.00%).', false);

        $this->actingAs($instructor)
            ->get(route('instructor.earnings.index'))
            ->assertOk()
            ->assertSee('Formula: Your Earnings = Sale amount - Platform fee.', false)
            ->assertSee('Refund policy: module purchase refunds are currently disabled.', false);
    }
}
