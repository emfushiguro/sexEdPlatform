<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class ContentReportFlowTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_learner_report_submission_notifies_admin_without_enum_string_cast_errors(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        /** @var User $instructor */
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'report_notify_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Report notify profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $this->actingAs($learner)
            ->post(route('learner.reports.store'), [
                'target_type' => 'module',
                'target_id' => $module->id,
                'reason_code' => 'misleading_information',
                'details' => '<p>Potentially misleading explanation in lesson content.</p>',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);
    }

    public function test_learner_can_submit_report_and_duplicate_active_report_is_merged(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        /** @var User $instructor */
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'report_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Report test profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $this->actingAs($learner)
            ->post(route('learner.reports.store'), [
                'target_type' => 'module',
                'target_id' => $module->id,
                'reason_code' => 'misleading_information',
                'details' => '<p>Potentially misleading explanation in lesson content.</p>',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('content_reports', 1);

        $this->actingAs($learner)
            ->post(route('learner.reports.store'), [
                'target_type' => 'module',
                'target_id' => $module->id,
                'reason_code' => 'harmful_material',
                'details' => '<p>Additional evidence with updated context.</p>',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseHas('content_reports', [
            'reporter_id' => $learner->id,
            'target_type' => 'module',
            'target_id' => $module->id,
            'reason_code' => 'harmful_material',
            'status' => 'submitted',
        ]);
    }
}
