<?php

namespace Tests\Unit\Services;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceFeatureEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_feature_reads_legacy_core_feature_aliases(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $legacyShieldFeature = FeatureCatalog::create([
            'key' => 'unlimited_shields',
            'name' => 'Unlimited Shields',
            'value_type' => 'boolean',
            'category' => 'learner',
            'is_active' => true,
        ]);

        $legacyCertificateFeature = FeatureCatalog::create([
            'key' => 'certificate_pdf_download_access',
            'name' => 'Certificate PDF Download Access',
            'value_type' => 'boolean',
            'category' => 'learner',
            'is_active' => true,
        ]);

        $legacyTextTranslatorFeature = FeatureCatalog::create([
            'key' => 'text_translation',
            'name' => 'Text Translator',
            'value_type' => 'boolean',
            'category' => 'learner',
            'is_active' => true,
        ]);

        $legacyVoiceTranslatorFeature = FeatureCatalog::create([
            'key' => 'voice_translator',
            'name' => 'Voice Speech Translator',
            'value_type' => 'boolean',
            'category' => 'learner',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyShieldFeature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyCertificateFeature->id,
            'is_enabled' => true,
            'is_unlimited' => false,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyTextTranslatorFeature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyVoiceTranslatorFeature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        $service = app(SubscriptionService::class);

        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS));
        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES));
        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::TEXT_TRANSLATOR));
        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::VOICE_SPEECH_TRANSLATOR));
    }

    public function test_has_feature_reads_legacy_username_alias(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $legacyUsernameFeature = FeatureCatalog::create([
            'key' => 'unlimited_username_changes',
            'name' => 'Unlimited Username Changes',
            'value_type' => 'boolean',
            'category' => 'learner',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyUsernameFeature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        $service = app(SubscriptionService::class);

        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE));
    }

    public function test_get_feature_quota_returns_value_for_quota_entitlement(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $quotaFeature = FeatureCatalog::create([
            'key' => 'monthly_streak_savers_quota',
            'name' => 'Monthly Streak Savers',
            'value_type' => 'quota',
            'category' => 'learner',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $quotaFeature->id,
            'is_enabled' => true,
            'quota_value' => 3,
            'is_unlimited' => false,
        ]);

        $service = app(SubscriptionService::class);

        $this->assertTrue($service->hasFeature($user, 'monthly_streak_savers_quota'));
        $this->assertSame(3, $service->getFeatureQuota($user, 'monthly_streak_savers_quota'));
    }

    public function test_instructor_entitlement_canonical_keys_resolve_legacy_aliases(): void
    {
        [$user, $plan] = $this->createActiveSubscription();

        $legacyPublishedLimitFeature = FeatureCatalog::create([
            'key' => 'max_published_modules',
            'name' => 'Published Modules Limit',
            'value_type' => 'quota',
            'category' => 'instructor',
            'is_active' => true,
        ]);

        $legacyPaidCapFeature = FeatureCatalog::create([
            'key' => 'paid_module_learner_cap',
            'name' => 'Paid Module Learner Cap',
            'value_type' => 'quota',
            'category' => 'instructor',
            'is_active' => true,
        ]);

        $legacyPublishPaidFeature = FeatureCatalog::create([
            'key' => 'can_publish_paid_module',
            'name' => 'Can Publish Paid Module',
            'value_type' => 'boolean',
            'category' => 'instructor',
            'is_active' => true,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyPublishedLimitFeature->id,
            'is_enabled' => true,
            'quota_value' => 3,
            'is_unlimited' => false,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyPaidCapFeature->id,
            'is_enabled' => true,
            'quota_value' => 50,
            'is_unlimited' => false,
        ]);

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $legacyPublishPaidFeature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        $service = app(SubscriptionService::class);

        $this->assertSame(3, $service->getFeatureQuota($user, SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT));
        $this->assertSame(50, $service->getFeatureQuota($user, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE));
        $this->assertTrue($service->hasFeature($user, SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES));
    }

    private function createActiveSubscription(): array
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Learner',
            'slug' => 'premium-learner-' . uniqid(),
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
}
