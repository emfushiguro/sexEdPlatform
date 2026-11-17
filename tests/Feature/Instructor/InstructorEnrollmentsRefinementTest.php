<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorEnrollmentsRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_details_show_scoped_lesson_and_quiz_progress(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');
        $learner = User::factory()->create();
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $enrollment = ModuleEnrollment::factory()->create(['module_id' => $module->id, 'user_id' => $learner->id, 'status' => 'approved', 'completion_percentage' => 0]);
        $lesson = \App\Models\Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true, 'title' => 'Published lesson']);
        \App\Models\Lesson::factory()->create(['module_id' => $module->id, 'is_published' => false, 'title' => 'Unpublished lesson']);
        \App\Models\UserProgress::query()->create(['user_id' => $learner->id, 'module_id' => $module->id, 'lesson_id' => $lesson->id, 'completed' => true, 'progress_percentage' => 100]);
        $quiz = \App\Models\Quiz::factory()->create(['module_id' => $module->id, 'lesson_id' => $lesson->id, 'title' => 'Lesson check', 'is_active' => true]);
        \App\Models\QuizAttempt::query()->create(['user_id' => $learner->id, 'quiz_id' => $quiz->id, 'score' => 60, 'passed' => false, 'started_at' => now()->subMinutes(2), 'completed_at' => now()]);
        \App\Models\QuizAttempt::query()->create(['user_id' => $learner->id, 'quiz_id' => $quiz->id, 'score' => 90, 'passed' => true, 'started_at' => now(), 'completed_at' => now()]);
        $other = User::factory()->create();
        \App\Models\QuizAttempt::query()->create(['user_id' => $other->id, 'quiz_id' => $quiz->id, 'score' => 13, 'passed' => false, 'started_at' => now(), 'completed_at' => now()]);

        $this->actingAs($instructor)->get(route('instructor.enrollments.show', $enrollment))
            ->assertOk()->assertSee('Published lesson')->assertDontSee('Unpublished lesson')
            ->assertSee('1 of 1 lessons completed')->assertSee('Lesson check')->assertSee('60%')->assertSee('90%')
            ->assertDontSee('13%')->assertDontSee('0% Progress');
    }

    public function test_enrollments_page_has_modern_layout_markers()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        // Create a module belonging to this instructor
        $module = Module::factory()->create(['created_by' => $instructor->id]);

        // Create a pending enrollment
        $learner = User::factory()->create();
        $modEnrollment = ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        $response->assertStatus(200);
        
        // Assert modern page header exists with left border
        $response->assertSee('Enrollments', false);
        
        // Assert refined card layout marker for status filtering
        $response->assertSee('data-enrollment-list', false);
        
        // Assert learner card section exists
        $response->assertSeeText($learner->name ?? $learner->first_name);
        $response->assertSeeText($module->title);
    }

    public function test_enrollments_page_shows_status_filter_tabs()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $learner = User::factory()->create();
        
        ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        // Assert status filter tabs exist
        $response->assertSee('All', false);
        $response->assertSee('Pending', false);
        $response->assertSee('Approved', false);
        $response->assertSee('Rejected', false);
    }

    public function test_enrollments_page_approval_actions_are_visible()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $learner = User::factory()->create();
        
        $enrollment = ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        // Assert approval action links exist
        $response->assertSee(route('instructor.enrollments.show', $enrollment), false);
        $response->assertSee(route('instructor.enrollments.approve', $enrollment), false);
        $response->assertSee(route('instructor.enrollments.reject', $enrollment), false);
    }
}
