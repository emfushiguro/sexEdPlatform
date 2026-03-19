<?php

namespace Tests\Unit\Models;

use App\Models\AdminActivityLog;
use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\UnitTestCase;

class SubscriptionDomainRelationsTest extends UnitTestCase
{
    public function test_subscription_plan_exposes_normalized_relations(): void
    {
        $plan = new SubscriptionPlan();

        $this->assertInstanceOf(HasMany::class, $plan->planPrices());
        $this->assertInstanceOf(HasMany::class, $plan->featureEntitlements());

        $casts = $plan->getCasts();

        $this->assertSame('array', $casts['features'] ?? null);
        $this->assertSame('boolean', $casts['is_active'] ?? null);
    }

    public function test_subscription_exposes_plan_price_relation_and_normalized_casts(): void
    {
        $subscription = new Subscription();

        $this->assertInstanceOf(BelongsTo::class, $subscription->planPrice());

        $casts = $subscription->getCasts();

        $this->assertSame('datetime', $casts['starts_at'] ?? null);
        $this->assertSame('datetime', $casts['ends_at'] ?? null);
        $this->assertSame('datetime', $casts['grace_ends_at'] ?? null);
        $this->assertSame('datetime', $casts['cancel_at'] ?? null);
        $this->assertSame('datetime', $casts['canceled_at'] ?? null);
        $this->assertSame('datetime', $casts['next_billing_at'] ?? null);
    }

    public function test_new_domain_models_define_expected_relations_and_accessors(): void
    {
        $price = new PlanPrice(['amount_minor' => 19900, 'currency' => 'PHP', 'duration_label' => 'Annual']);
        $entitlement = new PlanFeatureEntitlement();
        $feature = new FeatureCatalog();
        $log = new AdminActivityLog();

        $this->assertInstanceOf(BelongsTo::class, $price->plan());
        $this->assertInstanceOf(HasMany::class, $price->subscriptions());

        $this->assertSame('199.00', $price->amount);
        $this->assertSame('Annual', $price->duration_display);

        $this->assertInstanceOf(BelongsTo::class, $entitlement->plan());
        $this->assertInstanceOf(BelongsTo::class, $entitlement->feature());

        $this->assertInstanceOf(HasMany::class, $feature->planEntitlements());

        $this->assertInstanceOf(BelongsTo::class, $log->adminUser());
    }
}
