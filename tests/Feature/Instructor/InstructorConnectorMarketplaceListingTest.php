<?php

namespace Tests\Feature\Instructor;

use App\Models\FeatureCatalog;
use App\Models\InstructorApplication;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InstructorConnectorMarketplaceListingTest extends TestCase
{
    public function test_module_owner_can_publish_a_connector_marketplace_listing_without_a_plan(): void
    {
        config()->set('subscription_features.instructor_rollout_mode', 'strict');
        $instructor = $this->createInstructor();
        $this->assertFalse($instructor->subscriptions()->exists());
        $this->assertFalse(SubscriptionPlan::where('plan_audience', 'instructor')->exists());
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
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'module_id' => $module->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $instructor->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
        ]);
    }

    public function test_approved_adult_learner_can_publish_without_a_subscription(): void
    {
        config()->set('subscription_features.instructor_rollout_mode', 'strict');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $learner = User::factory()->create(['role' => 'learner', 'birthdate' => now()->subYears(25)]);
        $learner->assignRole('learner');
        $application = InstructorApplication::create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'college_graduate',
            'government_id_path' => 'test/id.pdf',
            'clearance_path' => 'test/clearance.pdf',
            'bio' => str_repeat('B', 120),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.instructor-applications.approve', $application), [
                'admin_message' => '<p>Your application has been approved.</p>',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $instructor = $learner->fresh();
        $this->assertTrue($instructor->hasRole('instructor'));
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertFalse($instructor->subscriptions()->exists());
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
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'module_id' => $module->id,
            'seller_user_id' => $instructor->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
        ]);
        $this->assertFalse($instructor->subscriptions()->exists());
    }

    public function test_instructor_plan_monetization_permissions_do_not_block_connector_publishing(): void
    {
        config()->set('subscription_features.instructor_rollout_mode', 'strict');
        $instructor = $this->createInstructor();
        $plan = $this->createRestrictiveInstructorPlan();
        Subscription::create([
            'user_id' => $instructor->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 299,
        ]);
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
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'module_id' => $module->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
        ]);
    }

    public function test_instructor_can_save_a_draft_listing_without_a_plan(): void
    {
        $instructor = $this->createInstructor();
        $module = Module::factory()->unpublished()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->put(route('instructor.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => config('billing.currency.code'),
                'seat_capacity' => 10,
                'action' => 'save',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'module_id' => $module->id,
            'status' => ModuleMarketplaceListing::STATUS_DRAFT,
        ]);
    }

    #[DataProvider('unpublishableModules')]
    public function test_module_visibility_and_capacity_are_still_required(array $attributes, string $error): void
    {
        $instructor = $this->createInstructor();
        $module = Module::factory()->create(array_merge([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => 'approved',
        ], $attributes));

        $this->actingAs($instructor)
            ->put(route('instructor.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => config('billing.currency.code'),
                'seat_capacity' => 10,
                'action' => 'publish',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors($error);

        $this->assertDatabaseMissing('module_marketplace_listings', ['module_id' => $module->id]);
    }

    public static function unpublishableModules(): array
    {
        return [
            'unapproved' => [['current_review_status' => 'pending'], 'listing'],
            'unpublished' => [['is_published' => false], 'listing'],
            'insufficient capacity' => [['enrollment_limit' => 5], 'capacity'],
        ];
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

    private function createRestrictiveInstructorPlan(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Restricted Instructor Plan',
            'slug' => 'marketplace-restricted-'.uniqid(),
            'description' => 'Marketplace test plan',
            'price' => 299,
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
                'is_enabled' => false,
                'is_unlimited' => false,
            ]);
        }

        return $plan;
    }
}
