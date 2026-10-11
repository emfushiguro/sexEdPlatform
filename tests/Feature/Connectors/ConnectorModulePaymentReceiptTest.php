<?php

namespace Tests\Feature\Connectors;

use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModulePaymentReceiptTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_receipt_shows_package_and_terms_snapshot(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_connector_receipt_1');

        $this->actingAs($manager)
            ->get(route('payment.receipt', $payment))
            ->assertOk()
            ->assertSee($connector->name)
            ->assertSee($module->title)
            ->assertSee('10 seats')
            ->assertSee('2026-10-05');
    }
}
