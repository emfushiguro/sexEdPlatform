<?php

namespace Tests\Feature\Connectors;

use App\Enums\SubscriptionStatus;
use App\Models\FeatureCatalog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Connectors\ConnectorEntitlementService;
use Tests\TestCase;

class ConnectorSubscriptionEntitlementTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_entitlements_require_active_connector_subscription(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $service = app(ConnectorEntitlementService::class);

        $this->assertFalse($service->hasEntitlement($connector, 'connector.seminars'));

        $plan = SubscriptionPlan::create([
            'name' => 'Connector Growth',
            'slug' => 'connector-growth',
            'description' => 'Connector plan',
            'price' => 100,
            'features' => [],
            'plan_audience' => 'connectors',
            'billing_mode' => 'monthly',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $feature = FeatureCatalog::create([
            'key' => 'connector.seminars',
            'name' => 'Connector Seminars',
            'value_type' => 'boolean',
            'category' => 'connectors',
            'is_active' => true,
        ]);
        $plan->featureEntitlements()->create(['feature_id' => $feature->id, 'is_enabled' => true]);

        Subscription::create([
            'user_id' => $owner->id,
            'connector_id' => $connector->id,
            'plan_id' => $plan->id,
            'plan' => 'connector',
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue(SubscriptionPlan::forConnectors()->whereKey($plan->id)->exists());
        $this->assertTrue($service->hasEntitlement($connector, 'connector.seminars'));
        $this->actingAs($owner)->get(route('connector.subscription', $connector))->assertOk()->assertSee('Connector Growth');
    }
}
