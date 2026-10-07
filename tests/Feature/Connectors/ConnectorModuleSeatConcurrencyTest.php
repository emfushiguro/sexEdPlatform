<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ConnectorModuleAccessRule;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleSeatConcurrencyTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_one_seat_has_one_unreleased_seat_consuming_assignment(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 1],
            purchaseOverrides: ['seat_capacity' => 1],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_assignment_2');
        $service = app(ConnectorModuleAccessService::class);
        $entitlement = $purchase->fresh()->entitlement;
        $service->storeRule($entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);

        $member = $this->createAdultConnectorMember($connector);
        $service->reconcileMember($member->connectorMemberships()->latest('id')->firstOrFail());
        $service->reconcileEntitlement($entitlement->fresh());

        $this->assertSame(1, ConnectorModuleSeatAssignment::query()
            ->where('connector_module_entitlement_id', $entitlement->id)
            ->where('consumes_seat', true)
            ->whereNull('released_at')
            ->count());
    }
}
