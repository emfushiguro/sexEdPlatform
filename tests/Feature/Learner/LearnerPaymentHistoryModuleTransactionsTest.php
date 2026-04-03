<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Tests\TestCase;

class LearnerPaymentHistoryModuleTransactionsTest extends TestCase
{
    public function test_payment_history_shows_module_purchase_entries(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);
        $learner->assignRole('learner');

        LearnerProfile::create([
            'user_id' => $learner->id,
            'username' => 'history_learner',
            'birthdate' => $learner->birthdate,
            'age_range' => 'adult_18_plus',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Paid Safety Module',
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
            'method' => 'gcash',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'MOD-HISTORY-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
                'module_title' => $module->title,
            ],
            'paid_at' => now(),
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'payment_id' => $payment->id,
            'amount' => 499,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_COMPLETED,
            'purchased_at' => now(),
        ]);

        ModuleSaleLedger::query()->create([
            'payment_id' => $payment->id,
            'module_purchase_id' => $purchase->id,
            'module_id' => $module->id,
            'instructor_id' => $module->created_by,
            'learner_id' => $learner->id,
            'learner_name_snapshot' => $learner->name,
            'currency' => 'PHP',
            'gross_amount' => 499,
            'basis_amount' => 499,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 50,
            'instructor_earnings_amount' => 449,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'pending',
            'occurred_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('payment.history'))
            ->assertOk()
            ->assertSee('Module Purchase')
            ->assertSee('Paid Safety Module');
    }
}
