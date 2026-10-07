<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleAccessManagementUiTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_access_page_is_manager_only_and_lists_rules_and_assignments(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_library_2');
        $member = $this->createAdultConnectorMember($connector);
        app(ConnectorModuleAccessService::class)->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertOk()
            ->assertSee('Access Management')
            ->assertSee('All members')
            ->assertSee($member->name)
            ->assertSee('Reconcile Access');

        $this->actingAs($member)
            ->get(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertForbidden();
    }
}
