<?php

namespace Tests\Feature\Admin;

use App\Models\ModuleSaleLedger;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorModuleRevenueVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorTestHelpers;

    public function test_admin_revenue_shows_connector_channel_buyer_and_seats_and_denies_hard_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState();
        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_revenue_1'));
        $ledger = ModuleSaleLedger::query()->where('payment_id', $payment->id)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.index'))
            ->assertOk()
            ->assertSee('Connector Marketplace', false)
            ->assertSee($connector->name, false)
            ->assertSee($manager->name, false)
            ->assertSee((string) $purchase->seat_capacity, false);

        $this->actingAs($admin)
            ->get(route('admin.monetization.module-revenue.transactions.show', $ledger))
            ->assertOk()
            ->assertSee('Connector Marketplace', false)
            ->assertSee($connector->name, false);

        $this->actingAs($admin)
            ->delete(route('admin.monetization.module-revenue.destroy', $ledger))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('module_sale_ledgers', ['id' => $ledger->id]);
    }
}
