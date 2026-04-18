<?php

namespace Tests\Feature\Seeders;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Support\SubscriptionFeatureKeys;
use Database\Seeders\InstructorBaselinePlanSeeder;
use Tests\TestCase;

class InstructorBaselinePlanSeederTest extends TestCase
{
    public function test_seeder_creates_default_instructor_free_plan_with_entitlements(): void
    {
        $this->seed(InstructorBaselinePlanSeeder::class);

        $plan = SubscriptionPlan::query()->where('slug', 'instructor-free-plan')->first();

        $this->assertNotNull($plan);
        $this->assertSame('instructor', (string) $plan->plan_audience);
        $this->assertSame('0.00', number_format((float) $plan->price, 2, '.', ''));

        $keys = [
            SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT,
            SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE,
            SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
        ];

        foreach ($keys as $key) {
            $feature = FeatureCatalog::query()->where('key', $key)->first();

            $this->assertNotNull($feature);
            $this->assertDatabaseHas('plan_feature_entitlements', [
                'plan_id' => $plan->id,
                'feature_id' => $feature->id,
            ]);
        }

        $this->assertSame(
            6,
            PlanFeatureEntitlement::query()->where('plan_id', $plan->id)->count()
        );

        foreach ([
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
        ] as $key) {
            $feature = FeatureCatalog::query()->where('key', $key)->firstOrFail();
            $entitlement = PlanFeatureEntitlement::query()
                ->where('plan_id', $plan->id)
                ->where('feature_id', $feature->id)
                ->first();

            $this->assertNotNull($entitlement);
            $this->assertTrue((bool) $entitlement->is_enabled, sprintf('%s should be enabled on free baseline plan', $key));
        }
    }
}
