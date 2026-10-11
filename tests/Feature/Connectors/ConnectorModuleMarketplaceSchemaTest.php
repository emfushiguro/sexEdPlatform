<?php

namespace Tests\Feature\Connectors;

use App\Enums\EnrollmentStatus;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleMarketplaceListing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConnectorModuleMarketplaceSchemaTest extends TestCase
{
    public function test_marketplace_tables_and_model_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('module_marketplace_listings', [
            'module_id', 'seller_type', 'seller_user_id', 'status',
            'price_amount', 'currency', 'seat_capacity', 'published_by', 'published_at',
            'unpublished_by', 'unpublished_at', 'suspended_by', 'suspended_at', 'suspension_reason',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_module_purchases', [
            'connector_id', 'module_marketplace_listing_id', 'module_id', 'purchased_by',
            'payment_id', 'status', 'gross_amount', 'currency', 'seat_capacity',
            'seller_type_snapshot', 'seller_user_id_snapshot', 'module_title_snapshot',
            'terms_version', 'terms_accepted_at', 'capacity_hold_expires_at', 'provider_paid_at',
            'purchased_at', 'failed_at', 'failure_reason', 'corrected_at', 'correction_reason',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_module_entitlements', [
            'connector_id', 'connector_module_purchase_id', 'module_marketplace_listing_id',
            'module_id', 'status', 'gross_amount', 'currency', 'seat_capacity',
            'activated_at', 'suspended_at', 'status_reason',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_module_access_rules', [
            'connector_module_entitlement_id', 'target_type', 'target_id', 'rule_key',
            'is_active', 'created_by',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_module_seat_assignments', [
            'connector_module_entitlement_id', 'user_id', 'connector_membership_id',
            'connector_module_access_rule_id', 'source_type', 'status', 'inactive_reason',
            'consumes_seat', 'assigned_at', 'released_at', 'revoked_at', 'reactivated_at', 'revoked_by',
        ]));
        $this->assertTrue(Schema::hasColumns('module_access_grants', [
            'user_id', 'module_id', 'source_type', 'source_id', 'connector_id',
            'status', 'granted_at', 'ended_at', 'reason',
        ]));
        $this->assertTrue(Schema::hasColumns('module_enrollments', [
            'inactive_reason', 'inactivated_at',
        ]));
        $this->assertTrue(Schema::hasColumn('module_purchases', 'capacity_hold_expires_at'));
        $this->assertTrue(Schema::hasColumns('module_sale_ledgers', [
            'sale_channel', 'connector_module_purchase_id', 'connector_id', 'buyer_user_id',
            'seller_type', 'seller_user_id', 'seller_name_snapshot',
            'seat_capacity_snapshot', 'seller_net_amount',
        ]));

        $this->assertInstanceOf(BelongsTo::class, (new ModuleMarketplaceListing())->module());
        $this->assertInstanceOf(HasMany::class, (new ModuleMarketplaceListing())->purchases());
        $this->assertInstanceOf(BelongsTo::class, (new ConnectorModulePurchase())->listing());
        $this->assertInstanceOf(BelongsTo::class, (new ConnectorModulePurchase())->connector());
        $this->assertInstanceOf(HasOne::class, (new ConnectorModulePurchase())->entitlement());
        $this->assertInstanceOf(BelongsTo::class, (new ConnectorModuleEntitlement())->purchase());
        $this->assertInstanceOf(HasMany::class, (new ConnectorModuleEntitlement())->rules());
        $this->assertInstanceOf(HasMany::class, (new ConnectorModuleEntitlement())->assignments());
        $this->assertInstanceOf(BelongsTo::class, (new ConnectorModuleAccessRule())->entitlement());
        $this->assertInstanceOf(HasMany::class, (new ConnectorModuleAccessRule())->assignments());
        $this->assertInstanceOf(BelongsTo::class, (new ConnectorModuleSeatAssignment())->entitlement());
        $this->assertInstanceOf(BelongsTo::class, (new ModuleAccessGrant())->module());

        $this->assertSame('inactive', EnrollmentStatus::Inactive->value);
        $this->assertSame('published', ModuleMarketplaceListing::STATUS_PUBLISHED);
        $this->assertSame('completed', ConnectorModulePurchase::STATUS_COMPLETED);
        $this->assertSame('active', ConnectorModuleEntitlement::STATUS_ACTIVE);
        $this->assertSame('all_members', ConnectorModuleAccessRule::TARGET_ALL_MEMBERS);
        $this->assertSame('active', ConnectorModuleSeatAssignment::STATUS_ACTIVE);
        $this->assertSame('active', ModuleAccessGrant::STATUS_ACTIVE);
    }
}
