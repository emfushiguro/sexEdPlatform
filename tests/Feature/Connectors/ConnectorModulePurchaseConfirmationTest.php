<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleSeatAssignment;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModulePurchaseConfirmationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_completed_purchase_confirmation_is_read_only_and_links_to_library(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_confirmation_1');

        $this->actingAs($manager)
            ->get(route('connector.modules.purchase.confirmation', [$connector, $purchase]))
            ->assertOk()
            ->assertSee('successfully purchased')
            ->assertSee('organization learning library')
            ->assertSee('Configure Access')
            ->assertSee('View Learning Library');

        $this->assertSame(0, ConnectorModuleSeatAssignment::query()->count());
    }
}
