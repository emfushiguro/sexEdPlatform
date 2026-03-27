<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerDeactivatedModuleBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_module_stays_visible_in_enrolled_history_list(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module] = $this->createLearnerWithApprovedEnrollmentOnDeactivatedModule();

        $this->actingAs($learner)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertSee($module->title, false);
    }

    public function test_deactivated_module_blocks_lesson_and_quiz_progression_endpoints(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module] = $this->createLearnerWithApprovedEnrollmentOnDeactivatedModule();

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'is_active' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertRedirect(route('learner.modules.show', $module));

        $this->actingAs($learner)
            ->get(route('quizzes.start', $quiz))
            ->assertRedirect(route('learner.modules.show', $module));
    }

    /**
     * @return array{0: User, 1: Module}
     */
    private function createLearnerWithApprovedEnrollmentOnDeactivatedModule(): array
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $learner = User::factory()->create([
            'role' => 'learner',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'learner_' . $learner->id,
            'birthdate' => now()->subYears(20)->toDateString(),
            'age_range' => 'adult_18_plus',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Bio',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => false,
            'min_age' => 18,
            'max_age' => 100,
        ]);

        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        return [$learner, $module];
    }
}
