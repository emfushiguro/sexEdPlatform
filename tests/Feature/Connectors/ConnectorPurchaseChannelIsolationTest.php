<?php

namespace Tests\Feature\Connectors;

use App\Models\ModuleAccessGrant;
use App\Models\ModulePurchase;
use App\Models\Payment;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\ModulePurchaseService;
use App\Services\Modules\ModuleAccessService;
use Tests\TestCase;

class ConnectorPurchaseChannelIsolationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_organization_purchase_does_not_grant_personal_ownership_to_the_purchaser(): void
    {
        [$manager, $connector, $module, , $purchase, $payment] = $this->createConnectorModulePaymentState();
        $originalOwner = $module->created_by;
        $this->assertFalse(app(ModulePurchaseService::class)->completePayment($payment));
        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_channel_org'));

        $this->assertDatabaseCount('module_purchases', 0);
        $this->assertDatabaseCount('module_access_grants', 0);
        $this->assertFalse(app(ModuleAccessService::class)->hasActiveAccess($manager, $module));
        $this->assertSame($connector->id, $purchase->fresh()->entitlement->connector_id);
        $this->assertSame($originalOwner, $module->fresh()->created_by);
    }

    public function test_personal_payment_only_grants_the_learner_and_does_not_create_connector_entitlement(): void
    {
        [$manager, $connector, $module] = $this->createConnectorModulePaymentState(['max_age' => 100]);
        $learner = $this->createAdultConnectorMember($connector);
        $personal = ModulePurchase::create([
            'user_id' => $learner->id, 'module_id' => $module->id,
            'amount' => 149, 'currency' => 'PHP', 'status' => 'pending',
        ]);
        $payment = Payment::create([
            'user_id' => $learner->id, 'amount' => 149, 'method' => 'gcash', 'status' => 'pending',
            'payment_details' => ['payment_scope' => 'module_purchase', 'module_id' => $module->id, 'module_purchase_id' => $personal->id],
        ]);
        $personal->update(['payment_id' => $payment->id]);

        $this->assertFalse(app(ConnectorModulePurchaseService::class)->completePayment($payment));
        $this->assertTrue(app(ModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_channel_personal'));
        $this->assertDatabaseCount('connector_module_entitlements', 0);
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($learner, $module));
        $this->assertFalse(app(ModuleAccessService::class)->hasActiveAccess($manager, $module));
        $this->assertDatabaseHas('module_access_grants', [
            'user_id' => $learner->id, 'source_type' => ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE,
        ]);
    }

    public function test_payment_cannot_finalize_another_organizations_purchase(): void
    {
        [, , , , $firstPurchase, $payment] = $this->createConnectorModulePaymentState();
        [, , , , $otherPurchase] = $this->createConnectorModulePaymentState();
        $payment->update(['payment_details' => array_merge($payment->payment_details, [
            'connector_module_purchase_id' => $otherPurchase->id,
        ])]);

        $this->assertFalse(app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_wrong_org'));
        $this->assertSame('pending', $firstPurchase->fresh()->status);
        $this->assertSame('pending', $otherPurchase->fresh()->status);
        $this->assertDatabaseCount('connector_module_entitlements', 0);
        $this->assertDatabaseCount('module_sale_ledgers', 0);
    }

    public function test_underpaid_payment_cannot_grant_an_organization_license(): void
    {
        [, , , , $purchase, $payment] = $this->createConnectorModulePaymentState();
        $payment->update(['amount' => 1]);
        $this->assertFalse(app(ConnectorModulePurchaseService::class)->completePayment($payment));
        $this->assertSame('pending', $purchase->fresh()->status);
        $this->assertDatabaseCount('connector_module_entitlements', 0);
    }
}
