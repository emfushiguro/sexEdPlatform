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
            ->assertSessionHas('learning_audio_event', 'complete');
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
            ->assertSessionMissing('learning_audio_event');
    }

    public function test_topic_that_auto_completes_lesson_still_flashes_one_complete(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithTopics(1);

        $this->actingAs($learner)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect()
            ->assertSessionHas('learning_audio_event', 'complete');

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);
    }

    public function test_new_manual_lesson_completion_flashes_complete(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect()
            ->assertSessionHas('learning_audio_event', 'complete');
    }

    public function test_revisited_lesson_completion_does_not_flash_complete(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertSessionHas('learning_audio_event', 'complete');

        $this->flushSession();

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event');
    }

    public function test_failed_or_unauthorized_completion_does_not_flash_audio(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson([], false);

        $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect()
            ->assertSessionMissing('learning_audio_event');

        $this->flushSession();

        ['learner' => $publishedLearner, 'lesson' => $publishedLesson] = $this->enrolledLearnerWithLesson([
            'is_published' => false,
        ]);

        $this->actingAs($publishedLearner)
            ->post(route('learner.lessons.complete', $publishedLesson))
            ->assertNotFound()
            ->assertSessionMissing('learning_audio_event');
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
