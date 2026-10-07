<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleSeatAssignment;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Connectors\ConnectorRoleService;
use Tests\TestCase;

class ConnectorModuleRoleReconciliationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_role_changes_reconcile_inherited_module_access(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 5],
            purchaseOverrides: ['seat_capacity' => 5],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_role_1');
        $member = $this->createAdultConnectorMember($connector);
        $membership = $member->connectorMemberships()->latest('id')->firstOrFail();
        $targetRole = app(ConnectorRoleService::class)->createRole($connector, [
            'name' => 'Module Learners',
            'permissions' => [],
        ]);
        $otherRole = app(ConnectorRoleService::class)->createRole($connector, [
            'name' => 'Other Members',
            'permissions' => [],
        ]);
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ROLE,
            'target_id' => $targetRole->id,
        ]);

        $membership->update(['connector_role_id' => $targetRole->id]);
        $access->reconcileMember($membership->fresh());
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'user_id' => $member->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE,
        ]);

        $membership->update(['connector_role_id' => $otherRole->id]);
        $access->reconcileMember($membership->fresh());
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'user_id' => $member->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
            'inactive_reason' => 'rule_or_membership_removed',
        ]);
    }
}
