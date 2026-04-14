<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ModuleFeedback;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class ModuleReviewVisualsTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_review_surfaces_render_icon_hearts_with_numeric_rating(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module] = $this->createLearnerAndModule();

        $reviewer = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
            'name' => 'Review Writer',
        ]);
        $reviewer->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $reviewer->id,
            'username' => 'writer_' . $reviewer->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Writer profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        ModuleFeedback::query()->create([
            'module_id' => $module->id,
            'learner_id' => $reviewer->id,
            'rating' => 4,
            'review_html' => '<p>Useful module.</p>',
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('4.0', false)
            ->assertSee('aria-label="4 out of 5 hearts"', false)
            ->assertSee('aria-label="Select 5 hearts"', false);
    }

    public function test_reviews_show_learner_avatar_and_display_name(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module] = $this->createLearnerAndModule();

        $reviewer = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
            'name' => 'Sample Learner',
        ]);
        $reviewer->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $reviewer->id,
            'username' => 'sample_' . $reviewer->id,
            'birthdate' => now()->subYears(15)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Avatar profile',
            'avatar_path' => 'avatars/sample.png',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        ModuleFeedback::query()->create([
            'module_id' => $module->id,
            'learner_id' => $reviewer->id,
            'rating' => 5,
            'review_html' => '<p>Great content.</p>',
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.reviews', $module))
            ->assertOk()
            ->assertSee('Sample Learner', false)
            ->assertSee('avatars/sample.png', false)
            ->assertSee('aria-label="Select 5 hearts"', false);
    }

    /**
     * @return array{0: User, 1: Module}
     */
    private function createLearnerAndModule(): array
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'visual_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Visual profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 13,
            'max_age' => 17,
            'enrollment_mode' => 'auto',
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Visual Lesson',
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

        return [$learner, $module];
    }
}
