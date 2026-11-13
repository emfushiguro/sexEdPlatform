<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LearningAudioCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_new_topic_completion_flashes_complete_once(): void
    {
        ['learner' => $learner, 'topic' => $topic] = $this->enrolledLearnerWithTopics(2);

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionHas('learning_audio_event', 'complete')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_repeated_topic_completion_does_not_flash_complete(): void
    {
        ['learner' => $learner, 'topic' => $topic] = $this->enrolledLearnerWithTopics(2);

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertSessionHas('learning_audio_event', 'complete');

        $this->flushSession();

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_topic_that_auto_completes_lesson_flashes_lesson_feedback_once(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithTopics(1);

        $response = $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect(route('learner.lessons.show', $lesson));

        $this->assertLessonCompletionFeedback($response, $learner, $lesson);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);

        $this->flushSession();

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_new_manual_lesson_completion_flashes_feedback_with_next_published_lesson(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        Lesson::factory()->create(['module_id' => $lesson->module_id, 'order' => 2, 'is_published' => false]);
        $nextLesson = Lesson::factory()->create(['module_id' => $lesson->module_id, 'order' => 3, 'is_published' => true]);
        Lesson::factory()->create(['module_id' => $lesson->module_id, 'order' => 4, 'is_published' => true]);

        $response = $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect();

        $this->assertLessonCompletionFeedback($response, $learner, $lesson, $nextLesson);
        $response->assertSessionHas('success', 'You completed this lesson and earned '.session('points_earned').' points.');
    }

    public function test_final_topic_feedback_links_to_next_lesson_and_preserves_topic_navigation(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithTopics(1);
        $nextLesson = Lesson::factory()->create(['module_id' => $lesson->module_id, 'order' => 2, 'is_published' => true]);

        $response = $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic), ['next_topic_index' => 1])
            ->assertRedirect(route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => 1]));

        $this->assertLessonCompletionFeedback($response, $learner, $lesson, $nextLesson);
    }

    public function test_final_topic_completes_existing_incomplete_lesson_progress(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithTopics(1);
        $progress = UserProgress::create([
            'user_id' => $learner->id,
            'module_id' => $lesson->module_id,
            'lesson_id' => $lesson->id,
            'completed' => false,
            'progress_percentage' => 0,
        ]);

        $response = $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect();

        $this->assertLessonCompletionFeedback($response, $learner, $lesson);
        $this->assertTrue($progress->fresh()->completed);
        $this->assertSame(100, $progress->fresh()->progress_percentage);
        $this->assertSame(1, UserProgress::where('user_id', $learner->id)->where('lesson_id', $lesson->id)->count());
    }

    public function test_new_topic_in_an_already_completed_lesson_keeps_topic_audio_only(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithTopics(1);

        $this->actingAs($learner)->post(route('learner.lessons.complete', $lesson));
        $this->flushSession();

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionHas('learning_audio_event', 'complete')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_revisited_lesson_completion_does_not_flash_complete(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $response = $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect();

        $this->assertLessonCompletionFeedback($response, $learner, $lesson);

        $this->flushSession();

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect()
            ->assertSessionHas('info', 'You have already completed this lesson.')
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_failed_or_unauthorized_completion_does_not_flash_audio(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson([], false);

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');

        $this->flushSession();

        ['learner' => $publishedLearner, 'lesson' => $publishedLesson] = $this->enrolledLearnerWithLesson([
            'is_published' => false,
        ]);

        $this->actingAs($publishedLearner)
            ->post(route('learner.lessons.complete', $publishedLesson))
            ->assertNotFound()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_unauthorized_topic_completion_does_not_flash_feedback_or_audio(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson([], false);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'text']);

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    public function test_unpublished_topic_completion_does_not_flash_feedback_or_audio(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson(['is_published' => false]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'text']);

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertNotFound()
            ->assertSessionMissing('learning_audio_event')
            ->assertSessionMissing('learning_feedback');
    }

    private function assertLessonCompletionFeedback(TestResponse $response, User $learner, Lesson $lesson, ?Lesson $nextLesson = null): void
    {
        $progress = UserProgress::where('user_id', $learner->id)
            ->where('lesson_id', $lesson->id)
            ->where('completed', true)
            ->firstOrFail();
        $primaryAction = $nextLesson
            ? ['id' => 'next', 'intent' => 'navigate', 'label' => 'Next lesson', 'url' => route('learner.lessons.show', $nextLesson)]
            : ['id' => 'module', 'intent' => 'navigate', 'label' => 'Module overview', 'url' => route('learner.modules.show', $lesson->module)];

        $response->assertSessionHas('learning_feedback', function (array $feedback) use ($learner, $lesson, $progress, $primaryAction): bool {
            return $feedback === [
                'id' => "lesson-completed:{$learner->id}:{$lesson->id}:{$progress->id}",
                'sourceId' => "lesson:{$lesson->id}",
                'type' => 'lesson_completed',
                'title' => 'Lesson complete',
                'message' => 'You completed this lesson and earned '.session('points_earned').' points.',
                'actions' => [
                    $primaryAction,
                    ['id' => 'dismiss', 'intent' => 'dismiss', 'label' => 'Review lesson'],
                ],
                'replacesSuccessToast' => true,
            ];
        })->assertSessionMissing('learning_audio_event');
    }

    /** @return array{learner: User, module: Module, lesson: Lesson} */
    private function enrolledLearnerWithLesson(array $lessonOverrides = [], bool $enroll = true): array
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');
        $learner->gamification()->create([
            'level' => 1,
            'xp' => 0,
            'score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create(array_merge([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ], $lessonOverrides));

        if ($enroll) {
            ModuleEnrollment::factory()->create([
                'user_id' => $learner->id,
                'module_id' => $module->id,
                'status' => EnrollmentStatus::Approved,
            ]);
        }

        return compact('learner', 'module', 'lesson');
    }

    /** @return array{learner: User, module: Module, lesson: Lesson, topic: LessonTopic} */
    private function enrolledLearnerWithTopics(int $topicCount): array
    {
        $result = $this->enrolledLearnerWithLesson();
        $topics = collect();

        for ($order = 1; $order <= $topicCount; $order++) {
            $topics->push(LessonTopic::factory()->create([
                'lesson_id' => $result['lesson']->id,
                'type' => 'text',
                'order' => $order,
                'is_prerequisite' => false,
            ]));
        }

        return [
            ...$result,
            'topic' => $topics->first(),
        ];
    }
}
