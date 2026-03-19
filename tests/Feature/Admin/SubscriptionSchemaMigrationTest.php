<?php

namespace Tests\Feature\Admin;

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
}
