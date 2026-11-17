<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleEntitlement;
use App\Services\PayMongoPaymentLinkService;
use Mockery\MockInterface;
use Tests\TestCase;

class ConnectorModulePaymentStatusTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_payment_status_polling_is_owner_scoped_and_idempotent(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'attributes' => [
                            'status' => 'completed',
                            'payments' => [['id' => 'pay_connector_status_1']],
                        ],
                    ],
                ]);
            $mock->shouldReceive('getActualPaymentIdFromCheckoutSession')
                ->once()
                ->andReturn('pay_connector_status_1');
        });

        $this->actingAs($manager)
            ->get(route('payment.status', $payment))
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->actingAs($manager)
            ->get(route('payment.status', $payment))
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertSame(1, ConnectorModuleEntitlement::query()
            ->where('connector_module_purchase_id', $purchase->id)
            ->count());

        $otherUser = \App\Models\User::factory()->create(['birthdate' => '2000-01-01']);
        $otherUser->identityVerifications()->create([
            'pathway' => 'adult', 'status' => 'approved', 'submission_round' => 1,
            'submitted_at' => now(), 'reviewed_at' => now(), 'approved_at' => now(),
        ]);
        $this->actingAs($otherUser)
            ->get(route('payment.status', $payment))
            ->assertForbidden();
    }
}
