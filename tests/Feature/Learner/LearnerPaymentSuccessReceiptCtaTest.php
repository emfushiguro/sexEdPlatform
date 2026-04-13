<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

class LearnerPaymentSuccessReceiptCtaTest extends TestCase
{
    public function test_payment_success_page_shows_receipt_button(): void
    {
        $learner = $this->createLearner();
        $module = $this->createModule();

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299,
            'method' => 'gcash',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'PAY-CTA-BTN-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
            ],
            'paid_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('payment.success', [
                'scope' => 'module_purchase',
                'module_id' => $module->id,
                'payment_id' => $payment->id,
            ]))
            ->assertOk()
            ->assertSee('View Receipt', false);
    }

    public function test_payment_success_page_receipt_button_points_to_specific_receipt_when_payment_resolves(): void
    {
        $learner = $this->createLearner();
        $module = $this->createModule();

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299,
            'method' => 'gcash',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'PAY-CTA-LINK-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
            ],
            'paid_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('payment.success', [
                'scope' => 'module_purchase',
                'module_id' => $module->id,
                'payment_id' => $payment->id,
            ]))
            ->assertOk()
            ->assertSee(route('payment.receipt', $payment), false);
    }

    public function test_payment_success_page_receipt_button_falls_back_to_payment_history_when_payment_not_resolved(): void
    {
        $learner = $this->createLearner();
        $module = $this->createModule();

        $this->actingAs($learner)
            ->get(route('payment.success', [
                'scope' => 'module_purchase',
                'module_id' => $module->id,
                'payment_id' => 999999,
            ]))
            ->assertOk()
            ->assertSee(route('payment.history'), false);
    }

    private function createLearner(): User
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        return $learner;
    }

    private function createModule(): Module
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        return Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'access_type' => 'paid',
            'price_amount' => 299,
            'price_currency' => 'PHP',
            'current_review_status' => null,
        ]);
    }
}
