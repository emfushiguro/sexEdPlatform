<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Tests\TestCase;

class AdminPaymentsAutomationUiTest extends TestCase
{
    public function test_admin_payments_index_no_longer_renders_manual_complete_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'ADMIN-AUTO-1',
            'payment_details' => [
                'payment_scope' => 'subscription',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertDontSee('/complete')
            ->assertDontSee('Complete payment');
    }

    public function test_admin_manual_complete_endpoint_is_not_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'ADMIN-AUTO-2',
            'payment_details' => [
                'payment_scope' => 'subscription',
            ],
        ]);

        $this->actingAs($admin)
            ->post('/admin/payments/' . $payment->id . '/complete')
            ->assertNotFound();
    }

    public function test_admin_payments_index_shows_module_purchase_transparency_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor Revenue']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner', 'name' => 'Learner Revenue']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Revenue Transparency Module',
            'is_published' => true,
            'access_type' => 'paid',
            'price_amount' => 499,
            'price_currency' => 'PHP',
            'current_review_status' => null,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 499,
            'method' => 'paymongo',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'ADMIN-MODULE-PAY-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
            ],
            'paid_at' => now(),
        ]);

        ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 499,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_COMPLETED,
            'purchased_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Module Purchase')
            ->assertSee('Revenue Transparency Module')
            ->assertSee('Instructor Revenue')
            ->assertSee('Learner Revenue');
    }

    public function test_admin_payment_receipt_uses_dedicated_admin_view_and_admin_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subWeek(),
            'end_date' => now()->addWeeks(3),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'method' => 'paymongo',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'ADMIN-RECEIPT-1',
            'paid_at' => now(),
            'payment_details' => [
                'payment_scope' => 'subscription',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.receipt', $payment))
            ->assertOk()
            ->assertViewIs('admin.payments.receipt')
            ->assertSee('Admin Payment Receipt')
            ->assertSee(route('admin.payments.show', $payment), false)
            ->assertDontSee(route('payment.history'), false)
            ->assertDontSee('Back to Subscriptions');
    }
}
