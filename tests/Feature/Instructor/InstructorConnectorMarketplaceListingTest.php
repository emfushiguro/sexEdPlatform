<?php

namespace Tests\Feature\Instructor;

use App\Models\FeatureCatalog;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Tests\TestCase;

class InstructorConnectorMarketplaceListingTest extends TestCase
{
    public function test_module_owner_can_publish_a_connector_marketplace_listing(): void
    {
        $instructor = $this->createInstructor();
        $this->createPaidDistributionPlan();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => config('billing.currency.code'),
                'seat_capacity' => 10,
                'action' => 'publish',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'module_id' => $module->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $instructor->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
        ]);
    }

    public function test_another_instructor_cannot_change_the_listing(): void
    {
        $owner = $this->createInstructor();
        $other = $this->createInstructor();
        $module = Module::factory()->create([
            'created_by' => $owner->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);

        $this->actingAs($other)
            ->put(route('instructor.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => 'PHP',
                'seat_capacity' => 10,
                'action' => 'publish',
            ])
            ->assertForbidden();
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        return $instructor;
    }

    private function createPaidDistributionPlan(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Marketplace Instructor Baseline',
            'slug' => 'marketplace-baseline-' . uniqid(),
            'description' => 'Marketplace test plan',
            'price' => 0,
            'features' => [],
            'plan_audience' => 'instructor',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ([
            'instructor_can_publish_paid_modules',
            'instructor_can_receive_paid_enrollments',
        ] as $key) {
            $feature = FeatureCatalog::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'value_type' => 'boolean',
                    'category' => 'instructor',
                    'is_active' => true,
                ],
            );
            PlanFeatureEntitlement::create([
                'plan_id' => $plan->id,
                'feature_id' => $feature->id,
                'is_enabled' => true,
                'is_unlimited' => true,
            ]);
        }

        return $plan;
    }
}
