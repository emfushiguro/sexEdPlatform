<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ModuleAccessGrant;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleSeatAssignmentTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_revocation_tombstone_overrides_rule_until_cleared(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            listingOverrides: ['seat_capacity' => 2],
            purchaseOverrides: ['seat_capacity' => 2],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_assignment_1');
        $service = app(ConnectorModuleAccessService::class);
        $entitlement = $purchase->fresh()->entitlement;
        $rule = $service->storeRule($entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);

        $assignment = $entitlement->fresh()->assignments()->where('user_id', $manager->id)->firstOrFail();
        $revoked = $service->revoke($entitlement, $manager, $manager, 'manual_revocation');

        $this->assertSame(ConnectorModuleSeatAssignment::STATUS_REVOKED, $revoked->status);
        $this->assertDatabaseHas('module_access_grants', [
            'source_type' => ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            'source_id' => $assignment->id,
            'status' => ModuleAccessGrant::STATUS_INACTIVE,
        ]);

        $service->reconcileEntitlement($entitlement->fresh());
        $this->assertSame(ConnectorModuleSeatAssignment::STATUS_REVOKED, $revoked->fresh()->status);

        $cleared = $service->clearRevocation($entitlement->fresh(), $manager, $manager);
        $this->assertNotSame(ConnectorModuleSeatAssignment::STATUS_REVOKED, $cleared->status);
        $this->assertDatabaseHas('module_access_grants', [
            'source_type' => ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            'source_id' => $assignment->id,
            'status' => ModuleAccessGrant::STATUS_ACTIVE,
        ]);
    }
}
