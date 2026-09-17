<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Enums\InteractiveActivityType;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InteractiveActivity;
use App\Models\InteractiveActivityProgress;
use App\Models\InteractiveCheckpointProgress;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\LessonTopicProgress;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleInteractionCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_completed_standalone_interactions_allow_certificate_claiming(): void
    {
        [$learner, $module] = $this->completedModuleWithStandaloneInteractions();

        $this->actingAs($learner)
            ->post(route('learner.certificates.check', $module))
            ->assertRedirect();

        $this->assertDatabaseHas('certificates', [
            'user_id' => $learner->id,
            'module_id' => $module->id,
        ]);
    }

    public function test_module_details_counts_completed_standalone_interactions(): void
    {
        [$learner, $module] = $this->completedModuleWithStandaloneInteractions();

        $this->actingAs($learner)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('3/3 topics', false)
            ->assertSee('Interactive Activity', false)
            ->assertSee('Interactive Checkpoint', false);
    }

    public function test_completed_perspective_feedback_counts_as_a_resolved_checkpoint(): void
    {
        [$learner, $module] = $this->completedModuleWithStandaloneInteractions();

        $this->actingAs($learner)
            ->post(route('learner.certificates.check', $module))
            ->assertRedirect();

        $this->assertDatabaseHas('certificates', [
            'user_id' => $learner->id,
            'module_id' => $module->id,
        ]);
    }

    /** @return array{User, Module} */
    private function completedModuleWithStandaloneInteractions(): array
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $learner->learnerProfile()->create([
            'username' => 'module-interactions-'.$learner->id,
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'final_quiz_id' => null,
        ]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);
        $requiredTopic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Required Topic',
            'type' => 'text',
            'order' => 1,
        ]);
        $activityTopic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Interactive Activity',
            'type' => 'interactive',
            'order' => 2,
        ]);
        $checkpointTopic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Interactive Checkpoint',
            'type' => 'interactive_checkpoint',
            'order' => 3,
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);
        UserProgress::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
        LessonTopicProgress::create([
            'user_id' => $learner->id,
            'lesson_topic_id' => $requiredTopic->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $activity = InteractiveActivity::factory()->betweenTopics()->create([
            'lesson_topic_id' => $activityTopic->id,
            'activity_type' => InteractiveActivityType::MATCHING,
            'revision' => 1,
        ]);
        InteractiveActivityProgress::create([
            'user_id' => $learner->id,
            'interactive_activity_id' => $activity->id,
            'activity_revision' => 1,
            'status' => 'completed',
            'working_state' => [],
            'attempt_count' => 1,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $checkpoint = QuizQuestion::create([
            'checkpoint_topic_id' => $checkpointTopic->id,
            'question_text' => 'Perspective checkpoint question',
            'question_type' => 'perspective_feedback',
            'points' => 0,
            'order' => 1,
        ]);
        InteractiveCheckpointProgress::create([
            'user_id' => $learner->id,
            'lesson_topic_id' => $checkpointTopic->id,
            'quiz_question_id' => $checkpoint->id,
            'status' => 'completed',
            'latest_answer' => ['pathway' => 'own', 'perspective_text' => 'My reflection'],
            'is_correct' => null,
            'attempt_count' => 1,
            'answered_at' => now(),
            'completed_at' => now(),
        ]);

        return [$learner, $module];
    }
}
