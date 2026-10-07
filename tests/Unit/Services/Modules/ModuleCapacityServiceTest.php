<?php

namespace Tests\Unit\Services\Modules;

use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleAccessGrant;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Models\Connector;
use App\Services\Modules\ModuleCapacityService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ModuleCapacityServiceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_usage_combines_independent_learners_connector_reservations_and_unexpired_holds(): void
    {
        $module = Module::factory()->create([
            'created_by' => User::factory()->create(['role' => 'admin'])->id,
            'content_owner_type' => 'admin',
            'enrollment_limit' => 12,
        ]);

        foreach (range(1, 2) as $sourceId) {
            ModuleAccessGrant::create([
                'user_id' => User::factory()->create(['role' => 'learner'])->id,
                'module_id' => $module->id,
                'source_type' => ModuleAccessGrant::SOURCE_DIRECT_ENROLLMENT,
                'source_id' => $sourceId,
                'status' => ModuleAccessGrant::STATUS_ACTIVE,
                'granted_at' => now(),
            ]);
        }

        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $listing = ModuleMarketplaceListing::create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 5,
        ]);
        $purchase = ConnectorModulePurchase::create([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $owner->id,
            'status' => ConnectorModulePurchase::STATUS_COMPLETED,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 5,
            'seller_type_snapshot' => 'platform',
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'purchased_at' => now(),
        ]);
        ConnectorModuleEntitlement::create([
            'connector_id' => $connector->id,
            'connector_module_purchase_id' => $purchase->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'status' => ConnectorModuleEntitlement::STATUS_ACTIVE,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 5,
            'activated_at' => now(),
        ]);

        foreach (range(1, 3) as $holdId) {
            $hold = ModulePurchase::create([
                'user_id' => User::factory()->create(['role' => 'learner'])->id,
                'module_id' => $module->id,
                'status' => ModulePurchase::STATUS_PENDING,
                'amount' => 2500,
                'currency' => 'PHP',
            ]);
            $hold->forceFill(['capacity_hold_expires_at' => now()->addMinutes(30)])->save();
        }

        $expired = ModulePurchase::create([
            'user_id' => User::factory()->create(['role' => 'learner'])->id,
            'module_id' => $module->id,
            'status' => ModulePurchase::STATUS_PENDING,
            'amount' => 2500,
            'currency' => 'PHP',
        ]);
        $expired->forceFill(['capacity_hold_expires_at' => now()->subMinute()])->save();

        $usage = app(ModuleCapacityService::class)->usage($module);

        $this->assertSame(2, $usage['independent_learners']);
        $this->assertSame(5, $usage['connector_reserved_seats']);
        $this->assertSame(3, $usage['pending_hold_seats']);
        $this->assertSame(10, $usage['used']);
        $this->assertSame(12, $usage['limit']);
        $this->assertSame(2, $usage['available']);
    }

    public function test_effective_limit_uses_the_lower_module_or_instructor_plan_cap(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $plan = \App\Models\SubscriptionPlan::create([
            'name' => 'Instructor Baseline',
            'slug' => 'capacity-baseline-' . uniqid(),
            'description' => 'Capacity baseline',
            'price' => 0,
            'features' => [],
            'plan_audience' => 'instructor',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $feature = \App\Models\FeatureCatalog::create([
            'key' => 'instructor_max_learners_per_paid_module',
            'name' => 'Paid module learner cap',
            'value_type' => 'quota',
            'category' => 'instructor',
            'is_active' => true,
        ]);
        \App\Models\PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'is_unlimited' => false,
            'quota_value' => 7,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'access_type' => 'paid',
            'price_amount' => 100,
            'enrollment_limit' => 12,
        ]);
        $capacity = app(ModuleCapacityService::class);

        $this->assertSame(7, $capacity->effectiveLimit($module));

        $module->update(['enrollment_limit' => 4]);
        $this->assertSame(4, $capacity->effectiveLimit($module->fresh()));

        $module->update([
            'content_owner_type' => 'admin',
            'enrollment_limit' => 12,
        ]);
        $this->assertSame(12, $capacity->effectiveLimit($module->fresh()));
    }
}
