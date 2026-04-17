<?php

namespace Tests\Feature\Admin;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerEntitlementNormalizationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalization_migration_enables_only_approved_paid_learner_entitlements(): void
    {
        $approvedKeys = [
            'unlimited_quiz_shields',
            'unlimited_username_change',
            'text_translator',
            'voice_speech_translator',
        ];

        $this->seedFeatureCatalogRows(array_merge($approvedKeys, [
            'downloadable_certificates',
        ]));

        $paidPlan = SubscriptionPlan::query()->create([
            'name' => 'Paid Learner Plan',
            'slug' => 'paid-learner-plan-' . uniqid(),
            'description' => 'Paid learner plan for normalization test',
            'price' => 199,
            'features' => ['downloadable_certificates', 'unlimited_username_change'],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $freePlan = SubscriptionPlan::query()->create([
            'name' => 'Free Learner Plan',
            'slug' => 'free-learner-plan-' . uniqid(),
            'description' => 'Free learner baseline',
            'price' => 0,
            'features' => ['downloadable_certificates'],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $certificateFeatureId = FeatureCatalog::query()->where('key', 'downloadable_certificates')->value('id');

        PlanFeatureEntitlement::query()->create([
            'plan_id' => $paidPlan->id,
            'feature_id' => $certificateFeatureId,
            'is_enabled' => true,
            'is_unlimited' => false,
            'quota_value' => null,
        ]);

        PlanFeatureEntitlement::query()->create([
            'plan_id' => $freePlan->id,
            'feature_id' => $certificateFeatureId,
            'is_enabled' => true,
            'is_unlimited' => false,
            'quota_value' => null,
        ]);

        $migration = require base_path('database/migrations/2026_04_16_150000_normalize_learner_paid_entitlements_for_translator_and_certificate_policy.php');
        $migration->up();

        foreach ($approvedKeys as $key) {
            $featureId = FeatureCatalog::query()->where('key', $key)->value('id');

            $this->assertDatabaseHas('plan_feature_entitlements', [
                'plan_id' => $paidPlan->id,
                'feature_id' => $featureId,
                'is_enabled' => 1,
                'is_unlimited' => 1,
            ]);
        }

        $this->assertDatabaseHas('plan_feature_entitlements', [
            'plan_id' => $paidPlan->id,
            'feature_id' => $certificateFeatureId,
            'is_enabled' => 0,
        ]);

        $this->assertDatabaseHas('plan_feature_entitlements', [
            'plan_id' => $freePlan->id,
            'feature_id' => $certificateFeatureId,
            'is_enabled' => 1,
        ]);

        $this->assertSame($approvedKeys, $paidPlan->fresh()->features);
        $this->assertSame(['downloadable_certificates'], $freePlan->fresh()->features);
    }

    private function seedFeatureCatalogRows(array $featureKeys): void
    {
        foreach ($featureKeys as $featureKey) {
            FeatureCatalog::query()->firstOrCreate(
                ['key' => $featureKey],
                [
                    'name' => str_replace('_', ' ', ucfirst($featureKey)),
                    'description' => null,
                    'value_type' => 'boolean',
                    'unit_label' => null,
                    'category' => 'learner',
                    'is_active' => true,
                ]
            );
        }
    }
}
