<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ConnectorModuleAccessRule;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConnectorModuleAccessRuleTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_all_member_rule_reconciles_assignments_and_rejects_current_overflow(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 1],
            purchaseOverrides: ['seat_capacity' => 1],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_access_rule_1');

        $service = app(ConnectorModuleAccessService::class);
        $rule = $service->storeRule(
            $purchase->fresh()->entitlement,
            $manager,
            ['target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS, 'target_id' => null],
        );

        $this->assertSame('all', $rule->rule_key);
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'connector_module_access_rule_id' => $rule->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE,
            'consumes_seat' => 1,
        ]);

        $secondMember = $this->createAdultConnectorMember($connector);
        $service->reconcileMember($secondMember->connectorMemberships()->latest('id')->firstOrFail());

        $this->assertSame(1, ConnectorModuleSeatAssignment::query()
            ->where('connector_module_entitlement_id', $purchase->fresh()->entitlement->id)
            ->where('status', ConnectorModuleSeatAssignment::STATUS_ACTIVE)
            ->where('consumes_seat', true)
            ->count());
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'user_id' => $secondMember->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
            'inactive_reason' => 'capacity_unavailable',
            'consumes_seat' => 0,
        ]);
    }

    public function test_duplicate_rule_key_is_rejected(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_access_rule_2');
        $service = app(ConnectorModuleAccessService::class);
        $entitlement = $purchase->fresh()->entitlement;

        $service->storeRule($entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);

        $this->expectException(ValidationException::class);
        $service->storeRule($entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);
    }
}
