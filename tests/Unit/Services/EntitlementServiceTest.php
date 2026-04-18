<?php

namespace Tests\Unit\Services;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EntitlementService;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_boolean_feature_when_enabled(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $feature = $this->firstOrCreateFeature([
            'key' => 'certificate_pdf_download',
            'name' => 'Certificate PDF Download',
            'value_type' => 'boolean',
            'category' => 'core',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'is_unlimited' => false,
        ]);

        $service = app(EntitlementService::class);

        $this->assertTrue($service->canAccessFeature($user, 'certificate_pdf_download'));
    }

    public function test_get_feature_quota_returns_quota_value_for_quota_feature(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $feature = $this->firstOrCreateFeature([
            'key' => 'monthly_streak_savers_quota',
            'name' => 'Monthly Streak Savers',
            'value_type' => 'quota',
            'category' => 'limits',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'quota_value' => 3,
            'is_unlimited' => false,
        ]);

        $service = app(EntitlementService::class);

        $this->assertSame(3, $service->getFeatureQuota($user, 'monthly_streak_savers_quota'));
    }

    public function test_unlimited_feature_allows_access_and_returns_null_quota(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $feature = $this->firstOrCreateFeature([
            'key' => SubscriptionFeatureKeys::UNLIMITED_SHIELDS,
            'name' => 'Unlimited Shields',
            'value_type' => 'boolean',
            'category' => 'core',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        $service = app(EntitlementService::class);

        $this->assertTrue($service->canAccessFeature($user, SubscriptionFeatureKeys::UNLIMITED_SHIELDS));
        $this->assertNull($service->getFeatureQuota($user, SubscriptionFeatureKeys::UNLIMITED_SHIELDS));
    }

    public function test_current_boolean_entitlement_keys_are_readable_from_plan_entitlements(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $unlimitedShields = $this->firstOrCreateFeature([
            'key' => SubscriptionFeatureKeys::UNLIMITED_SHIELDS,
            'name' => 'Unlimited Shields',
            'value_type' => 'boolean',
            'category' => 'core',
            'is_active' => true,
        ]);

        $textTranslation = $this->firstOrCreateFeature([
            'key' => SubscriptionFeatureKeys::TEXT_TRANSLATION,
            'name' => 'Text Translator',
            'value_type' => 'boolean',
            'category' => 'core',
            'is_active' => true,
        ]);

        $voiceTranslator = $this->firstOrCreateFeature([
            'key' => SubscriptionFeatureKeys::VOICE_TRANSLATOR,
            'name' => 'Voice Speech Translator',
            'value_type' => 'boolean',
            'category' => 'core',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $unlimitedShields->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $textTranslation->id,
            'is_enabled' => true,
            'is_unlimited' => false,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $voiceTranslator->id,
            'is_enabled' => true,
            'is_unlimited' => false,
        ]);

        $service = app(EntitlementService::class);

        $this->assertTrue($service->canAccessFeature($user, SubscriptionFeatureKeys::UNLIMITED_SHIELDS));
        $this->assertTrue($service->canAccessFeature($user, SubscriptionFeatureKeys::TEXT_TRANSLATOR));
        $this->assertTrue($service->canAccessFeature($user, SubscriptionFeatureKeys::VOICE_SPEECH_TRANSLATOR));
    }

    public function test_missing_feature_falls_back_to_denied_and_null_quota(): void
    {
        [$user] = $this->createActiveSubscription();

        $service = app(EntitlementService::class);

        $this->assertFalse($service->canAccessFeature($user, 'non_existent_feature'));
        $this->assertNull($service->getFeatureQuota($user, 'non_existent_feature'));
    }

    public function test_subscription_summary_contains_plan_and_status_context(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $service = app(EntitlementService::class);
        $summary = $service->getSubscriptionSummary($user);

        $this->assertSame($plan->id, $summary['plan_id']);
        $this->assertSame('active', $summary['status']);
        $this->assertTrue($summary['has_subscription']);
    }

    private function createActiveSubscription(): array
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Learner',
            'slug' => 'premium-learner',
            'description' => 'Premium access',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 199,
            'auto_renew' => true,
        ]);

        return [$user, $plan];
    }

    private function firstOrCreateFeature(array $attributes): FeatureCatalog
    {
        $key = (string) $attributes['key'];

        return FeatureCatalog::query()->firstOrCreate(
            ['key' => $key],
            $attributes,
        );
    }
}
