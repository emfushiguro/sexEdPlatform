<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorModuleEarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_access_earnings_index_and_detail_with_scoped_data(): void
    {
        $this->withoutVite();

        $instructorA = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor Alpha']);
        $instructorA->assignRole('instructor');

        $instructorB = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor Beta']);
        $instructorB->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner', 'name' => 'Learner One']);
        $learner->assignRole('learner');

        $moduleA = Module::factory()->create([
            'created_by' => $instructorA->id,
            'title' => 'Paid Module A',
            'access_type' => 'paid',
            'price_amount' => 199.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $moduleB = Module::factory()->create([
            'created_by' => $instructorB->id,
            'title' => 'Paid Module B',
            'access_type' => 'paid',
            'price_amount' => 299.99,
            'price_currency' => 'PHP',
            'is_published' => true,
        ]);

        $paymentA = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 199.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'EARN-1',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $moduleA->id],
            'paid_at' => now()->subDay(),
        ]);

        $paymentB = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299.99,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'EARN-2',
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

        $ledgerA = ModuleSaleLedger::query()->create([
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
            'payout_status' => 'pending',
            'occurred_at' => now()->subDay(),
        ]);

        $ledgerB = ModuleSaleLedger::query()->create([
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
            'payout_status' => 'payable',
            'occurred_at' => now(),
        ]);

        $indexResponse = $this->actingAs($instructorA)
            ->get(route('instructor.earnings.index'))
            ->assertOk()
            ->assertSee('Module Earnings', false)
            ->assertSee('Total Sales', false)
            ->assertSee('Paid Module A', false)
            ->assertSee('Your Earnings', false)
            ->assertSee('title="View transaction details"', false);

        $indexHtml = $indexResponse->getContent();
        $this->assertStringContainsString('Paid Module A', $indexHtml);
        $this->assertStringNotContainsString('Paid Module B', $indexHtml);

        $this->actingAs($instructorA)
            ->get(route('instructor.earnings.show', $ledgerA))
            ->assertOk()
            ->assertSee('Transaction Details', false)
            ->assertSee('Learner One', false)
            ->assertSee('Paid Module A', false);

        $this->actingAs($instructorA)
            ->get(route('instructor.earnings.show', $ledgerB))
            ->assertNotFound();
    }
}
