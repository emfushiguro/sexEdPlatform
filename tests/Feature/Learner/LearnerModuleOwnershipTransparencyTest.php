<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\AdminCreatorProfile;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LearnerModuleOwnershipTransparencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_owned_module_renders_admin_creator_information_card_and_view_full_link(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('instructor', 'web');
        Role::findOrCreate('admin', 'web');

        [$learner, $module, $admin] = $this->createEnrolledModuleOwnedByAdmin();

        AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Admin Creator Profile',
            'bio' => 'Builds platform-owned learning modules.',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('Admin Creator Information', false)
            ->assertSee('View Full Information page', false)
            ->assertSee(route('learner.admin-creators.show', $admin), false)
            ->assertSee('Conscious Connections Team', false)
            ->assertSee('by Admin Creator Profile', false)
            ->assertDontSee('Message instructor', false);
    }

    public function test_instructor_owned_module_keeps_instructor_information_flow(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('instructor', 'web');
        Role::findOrCreate('admin', 'web');

        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'ownership_learner_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Ownership learner profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 13,
            'max_age' => 17,
            'enrollment_mode' => 'auto',
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Ownership Lesson',
            'order' => 1,
            'is_published' => true,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('Instructor Information', false)
            ->assertDontSee('Admin Creator Information', false)
            ->assertSee('View Full Background Information', false);
    }

    /**
     * @return array{0: User, 1: Module, 2: User}
     */
    private function createEnrolledModuleOwnedByAdmin(): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'ownership_admin_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Ownership learner profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 13,
            'max_age' => 17,
            'enrollment_mode' => 'auto',
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Ownership Lesson',
            'order' => 1,
            'is_published' => true,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        return [$learner, $module, $admin];
    }
}
