<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class ConnectorModuleSuspensionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_and_user_suspension_block_learning_without_deleting_access_history(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
            listingOverrides: ['seat_capacity' => 5],
            purchaseOverrides: ['seat_capacity' => 5],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_suspend_1');
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);
        app(ModuleAccessService::class)->assertCanLearn($manager, $module);

        $connector->update(['status' => 'suspended']);
        $this->expectException(AuthorizationException::class);
        app(ModuleAccessService::class)->assertCanLearn($manager, $module);
    }
}
