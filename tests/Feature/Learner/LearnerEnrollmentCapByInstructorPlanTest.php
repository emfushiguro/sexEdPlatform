<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\FeatureCatalog;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Tests\TestCase;

class LearnerEnrollmentCapByInstructorPlanTest extends TestCase
{
    public function test_instructor_plan_cap_routes_new_enrollment_to_pending_when_full(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->createInstructorBaselineWithFreeCap(1);

        $existingLearner = $this->createLearner('existing_plan_cap_learner');
        $newLearner = $this->createLearner('new_plan_cap_learner');

        $module = Module::factory()->create([
            'title' => 'Plan capped module',
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'enrollment_mode' => 'auto',
            'enrollment_limit' => null,
            'access_type' => 'free',
            'price_amount' => null,
            'min_age' => 18,
            'max_age' => 30,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $existingLearner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($newLearner)
            ->post(route('learner.modules.enroll', $module))
            ->assertRedirect(route('learner.modules.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('module_enrollments', [
            'user_id' => $newLearner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    private function createLearner(string $username): User
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => $username,
            'birthdate' => $learner->birthdate,
        ]);

        return $learner;
    }

    private function createInstructorBaselineWithFreeCap(int $cap): SubscriptionPlan
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Instructor Baseline',
            'slug' => 'instructor-baseline-' . uniqid(),
            'description' => 'Baseline learner cap test plan',
            'price' => 0,
            'features' => [],
            'plan_audience' => 'instructor',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $feature = FeatureCatalog::query()->updateOrCreate(
            ['key' => 'instructor_max_learners_per_free_module'],
            [
                'name' => 'Free Module Learner Cap',
                'description' => 'Free Module Learner Cap',
                'value_type' => 'quota',
                'unit_label' => 'learners',
                'category' => 'instructor',
                'is_active' => true,
            ]
        );

        PlanFeatureEntitlement::query()->create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'quota_value' => $cap,
            'is_unlimited' => false,
        ]);

        return $plan;
    }
}
