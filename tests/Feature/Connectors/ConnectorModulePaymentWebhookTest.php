<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\ConnectorModuleEntitlement;
use Tests\TestCase;

class ConnectorModulePaymentWebhookTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_payment_webhook_completes_the_purchase_without_subscription_activation(): void
    {
        $this->withoutMiddleware();
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();

        $payload = [
            'data' => [
                'id' => 'evt_connector_paid_1',
                'attributes' => [
                    'type' => 'link.payment.paid',
                    'data' => [
                        'id' => 'pay_connector_paid_1',
                        'attributes' => [
                            'amount' => 250000,
                            'source' => ['type' => 'gcash'],
                            'metadata' => [
                                'payment_scope' => 'connector_module_purchase',
                                'connector_id' => $connector->id,
                                'connector_module_purchase_id' => $purchase->id,
                                'payment_id' => $payment->id,
                                'user_id' => $manager->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('webhook.paymongo'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('connector_module_purchases', [
            'id' => $purchase->id,
            'status' => 'completed',
        ]);
        $this->assertSame(1, ConnectorModuleEntitlement::query()
            ->where('connector_module_purchase_id', $purchase->id)
            ->count());

        $this->postJson(route('webhook.paymongo'), $payload)->assertOk();
        $this->assertDatabaseCount('connector_module_entitlements', 1);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
        $this->assertDatabaseCount('connector_module_purchases', 1);
    }
}
