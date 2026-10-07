<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ModuleAccessGrant;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleAccessService;
use Tests\TestCase;

class ConnectorModuleGuardianApprovalTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_minor_assignment_reserves_pending_guardian_seat_then_activates(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 5],
            purchaseOverrides: ['seat_capacity' => 5],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_guardian_1');
        $minor = $this->createMinorLearner(10);
        $membership = $connector->memberships()->create([
            'user_id' => $minor->id,
            'connector_role_id' => $connector->roles()->where('is_owner', true)->value('id'),
            'status' => 'active',
            'accepted_at' => now(),
        ]);
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $minor->id,
        ]);

        $assignment = $minor->connectorModuleSeatAssignments()->firstOrFail();
        $this->assertSame(ConnectorModuleSeatAssignment::STATUS_PENDING_GUARDIAN, $assignment->status);
        $this->assertDatabaseHas('module_access_grants', [
            'source_type' => ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            'source_id' => $assignment->id,
            'status' => ModuleAccessGrant::STATUS_PENDING,
        ]);

        $assignment->update(['status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE]);
        app(ModuleAccessService::class)->activateSource(
            ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            $assignment->id,
        );

        $this->assertDatabaseHas('module_enrollments', [
            'user_id' => $minor->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);
    }
}
