<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleEnrollment;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Connectors\ConnectorInvitationService;
use App\Services\Modules\ModuleAccessService;
use Tests\TestCase;

class ConnectorModuleMembershipLifecycleTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_membership_removal_releases_assignment_and_preserves_learning_state(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 5],
            purchaseOverrides: ['seat_capacity' => 5],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_membership_1');
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);
        $member = $this->createAdultConnectorMember($connector);
        $membership = $member->connectorMemberships()->latest('id')->firstOrFail();
        $access->reconcileMember($membership);

        $assignment = ConnectorModuleSeatAssignment::query()
            ->where('user_id', $member->id)
            ->firstOrFail();
        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $member->id)
            ->where('module_id', $module->id)
            ->firstOrFail();
        $enrollment->update(['completion_percentage' => 55]);

        app(ConnectorInvitationService::class)->remove($membership);

        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'id' => $assignment->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
            'inactive_reason' => 'membership_removed',
            'consumes_seat' => 0,
        ]);
        $this->assertDatabaseHas('module_access_grants', [
            'source_type' => ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            'source_id' => $assignment->id,
            'status' => ModuleAccessGrant::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => 'inactive',
            'completion_percentage' => 55,
        ]);
    }
}
