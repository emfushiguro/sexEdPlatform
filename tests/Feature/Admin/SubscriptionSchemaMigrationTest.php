<?php

namespace Tests\Feature\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalized_subscription_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('plan_prices'));
        $this->assertTrue(Schema::hasTable('feature_catalog'));
        $this->assertTrue(Schema::hasTable('plan_feature_entitlements'));
        $this->assertTrue(Schema::hasTable('admin_activity_logs'));
    }

    public function test_subscribers_table_has_normalized_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('subscribers', 'plan_price_id'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'starts_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'ends_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'grace_ends_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'cancel_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'canceled_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'next_billing_at'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'source_provider'));
        $this->assertTrue(Schema::hasColumn('subscribers', 'source_reference'));
    }

    public function test_subscription_plans_support_archive_lifecycle_columns_and_scopes(): void
    {
        $this->assertTrue(Schema::hasColumn('subscription_plans', 'archived_at'));

        $activePlan = SubscriptionPlan::create([
            'name' => 'Active Plan',
            'slug' => 'active-plan',
            'description' => 'Active test plan',
            'price' => 99,
            'features' => ['alpha'],
            'is_active' => true,
        ]);

        $inactivePlan = SubscriptionPlan::create([
            'name' => 'Inactive Plan',
            'slug' => 'inactive-plan',
            'description' => 'Inactive test plan',
            'price' => 49,
            'features' => ['beta'],
            'is_active' => false,
        ]);

        $archivedPlan = SubscriptionPlan::create([
            'name' => 'Archived Plan',
            'slug' => 'archived-plan',
            'description' => 'Archived test plan',
            'price' => 79,
            'features' => ['gamma'],
            'is_active' => false,
            'archived_at' => now(),
        ]);

        $activeIds = SubscriptionPlan::query()->active()->pluck('id');
        $notArchivedIds = SubscriptionPlan::query()->notArchived()->pluck('id');
        $archivedIds = SubscriptionPlan::query()->archived()->pluck('id');

        $this->assertTrue($activeIds->contains($activePlan->id));
        $this->assertFalse($activeIds->contains($archivedPlan->id));

        $this->assertTrue($notArchivedIds->contains($activePlan->id));
        $this->assertTrue($notArchivedIds->contains($inactivePlan->id));
        $this->assertFalse($notArchivedIds->contains($archivedPlan->id));

        $this->assertTrue($archivedIds->contains($archivedPlan->id));

        $this->assertFalse($activePlan->fresh()->isArchived());
        $this->assertTrue($archivedPlan->fresh()->isArchived());

        $this->assertNotNull($inactivePlan->id);
    }
}
