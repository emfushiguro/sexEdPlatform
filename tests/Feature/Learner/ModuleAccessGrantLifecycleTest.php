<?php

namespace Tests\Feature\Learner;

use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\Module;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleEnrollment;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Services\Modules\ModuleAccessService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ModuleAccessGrantLifecycleTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_multiple_sources_keep_learning_active_until_the_final_source_ends(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $module = Module::factory()->create();
        $enrollment = ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'completion_percentage' => 65,
        ]);
        $individualPurchase = ModulePurchase::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => ModulePurchase::STATUS_COMPLETED,
            'amount' => 2500,
            'currency' => 'PHP',
            'purchased_at' => now(),
        ]);

        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $listing = ModuleMarketplaceListing::create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 1,
        ]);
        $organizationPurchase = ConnectorModulePurchase::create([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $owner->id,
            'status' => ConnectorModulePurchase::STATUS_COMPLETED,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 1,
            'seller_type_snapshot' => 'platform',
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'purchased_at' => now(),
        ]);
        $entitlement = ConnectorModuleEntitlement::create([
            'connector_id' => $connector->id,
            'connector_module_purchase_id' => $organizationPurchase->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'status' => ConnectorModuleEntitlement::STATUS_ACTIVE,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 1,
            'activated_at' => now(),
        ]);
        $assignment = ConnectorModuleSeatAssignment::create([
            'connector_module_entitlement_id' => $entitlement->id,
            'user_id' => $learner->id,
            'connector_membership_id' => $connector->memberships()->first()->id,
            'source_type' => ConnectorModuleSeatAssignment::SOURCE_DIRECT,
            'status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE,
            'consumes_seat' => true,
            'assigned_at' => now(),
        ]);

        $service = app(ModuleAccessService::class);
        $service->grantIndividualPurchase($individualPurchase);
        $service->grantConnectorAssignment($assignment);

        $service->deactivateSource(
            ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            $assignment->id,
            'membership_removed',
        );

        $this->assertTrue($service->hasActiveAccess($learner, $module));

        $service->deactivateSource(
            ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE,
            $individualPurchase->id,
            'corrected',
        );

        $this->assertFalse($service->hasActiveAccess($learner, $module));
        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => 'inactive',
            'completion_percentage' => 65,
            'inactive_reason' => 'no_active_access_source',
        ]);
    }
}
