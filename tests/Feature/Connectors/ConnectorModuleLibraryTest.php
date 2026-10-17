<?php

namespace Tests\Feature\Connectors;

use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleLibraryTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_purchased_library_shows_immutable_purchase_and_derived_seat_counts(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_library_1');
        $listing->update(['price_amount' => 9000, 'seat_capacity' => 25]);

        $this->actingAs($manager)
            ->get(route('connector.modules.library.index', $connector))
            ->assertOk()
            ->assertSee('Purchased Modules')
            ->assertSee($module->title)
            ->assertSee('PHP 2,500.00')
            ->assertSee('0 used')
            ->assertSee('10 available');

        $this->actingAs($manager)
            ->get(route('connector.modules.library.show', [$connector, $purchase->fresh()->entitlement]))
            ->assertOk()
            ->assertSee('Immutable purchase record')
            ->assertSee('2026-10-05')
            ->assertSee('completed');
    }
}
