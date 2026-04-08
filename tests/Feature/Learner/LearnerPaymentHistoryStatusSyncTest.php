<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use Mockery\MockInterface;
use Tests\TestCase;

class LearnerPaymentHistoryStatusSyncTest extends TestCase
{
    public function test_payment_history_reconciles_pending_module_payment_to_completed(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'access_type' => 'paid',
            'price_amount' => 299,
            'price_currency' => 'PHP',
            'enrollment_mode' => 'auto',
            'current_review_status' => null,
        ]);

        $purchase = ModulePurchase::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'amount' => 299,
            'currency' => 'PHP',
            'status' => ModulePurchase::STATUS_PENDING,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => null,
            'amount' => 299,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'HISTORY-SYNC-1',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
                'module_purchase_id' => $purchase->id,
                'paymongo_checkout_session_id' => 'cs_history_sync_1',
            ],
        ]);

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_history_sync_1',
                        'attributes' => [
                            'status' => 'completed',
                            'payments' => [
                                ['id' => 'pay_history_sync_1'],
                            ],
                        ],
                    ],
                ]);

            $mock->shouldReceive('getActualPaymentIdFromCheckoutSession')
                ->once()
                ->andReturn('pay_history_sync_1');
        });

        $this->actingAs($learner)
            ->get(route('payment.history'))
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
    }
}
