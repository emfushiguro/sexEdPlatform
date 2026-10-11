<?php

namespace Tests\Feature\Admin;

use App\Models\InteractiveActivity;
use App\Models\InteractiveActivityProgress;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\Content\ModuleRevisionCompatibilityService;
use App\Services\ContentGovernanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\DatabaseTestCase;

class ConnectorModuleRevisionCompatibilityTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_additions_and_edits_keep_existing_identifiers_compatible(): void
    {
        [$admin, $module, $lesson, $topic, $activity, $quiz] = $this->contentFixture();
        $published = $this->createRevision($module, $admin, $this->snapshot($module, $lesson, $topic, $activity, $quiz, 'Published'));
        $module->forceFill(['published_revision_id' => $published->id])->save();

        $additionalLesson = Lesson::factory()->create(['module_id' => $module->id]);
        $candidate = $this->createRevision($module, $admin, $this->snapshot(
            $module,
            $lesson,
            $topic,
            $activity,
            $quiz,
            'Edited',
            $additionalLesson,
        ));

        app(ModuleRevisionCompatibilityService::class)->assertCompatible($module->fresh(), $candidate);

        $this->assertSame($published->id, $module->fresh()->published_revision_id);
        $this->assertSame('approved', $published->fresh()->status);
    }

    public function test_removal_without_history_can_be_approved(): void
    {
        [$admin, $module, $lesson, $topic, $activity, $quiz] = $this->contentFixture();
        $published = $this->createRevision($module, $admin, $this->snapshot($module, $lesson, $topic, $activity, $quiz, 'Published'));
        $module->forceFill([
            'published_revision_id' => $published->id,
            'current_review_status' => ContentGovernanceService::STATUS_IN_REVIEW,
        ])->save();
        $candidate = $this->createRevision($module, $admin, $this->emptySnapshot($module), 'in_review');
        $reviewRequest = ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $candidate->id,
            'status' => ContentGovernanceService::STATUS_IN_REVIEW,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $approved = app(ContentGovernanceService::class)->approveReview($reviewRequest, $admin);

        $this->assertSame($candidate->id, $approved->id);
        $this->assertSame($candidate->id, $module->fresh()->published_revision_id);
        $this->assertSame('approved', $candidate->fresh()->status);
    }

    public function test_removal_with_persisted_history_is_rejected_before_approval(): void
    {
        [$admin, $module, $lesson, $topic, $activity, $quiz] = $this->contentFixture();
        $published = $this->createRevision($module, $admin, $this->snapshot($module, $lesson, $topic, $activity, $quiz, 'Published'));
        $module->forceFill([
            'published_revision_id' => $published->id,
            'current_review_status' => ContentGovernanceService::STATUS_IN_REVIEW,
        ])->save();
        $candidate = $this->createRevision($module, $admin, $this->emptySnapshot($module), 'in_review');
        $reviewRequest = ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $candidate->id,
            'status' => ContentGovernanceService::STATUS_IN_REVIEW,
            'submitted_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $learner = User::factory()->create(['role' => 'learner']);
        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
        $topic->progress()->create([
            'user_id' => $learner->id,
            'completed' => true,
            'completed_at' => now(),
        ]);
        InteractiveActivityProgress::query()->create([
            'user_id' => $learner->id,
            'interactive_activity_id' => $activity->id,
            'activity_revision' => $activity->revision,
            'status' => 'completed',
            'attempt_count' => 1,
            'completed_at' => now(),
        ]);
        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'answers' => ['question' => 'answer'],
            'score' => 100,
            'passed' => true,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        try {
            app(ContentGovernanceService::class)->approveReview($reviewRequest, $admin);
            $this->fail('A revision removing persisted learner history should be rejected.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertArrayHasKey('lessons', $errors);
            $this->assertArrayHasKey('topics', $errors);
            $this->assertArrayHasKey('interactive_activities', $errors);
            $this->assertArrayHasKey('quizzes', $errors);
        }

        $this->assertSame($published->id, $module->fresh()->published_revision_id);
        $this->assertSame('in_review', $candidate->fresh()->status);
        $this->assertSame('in_review', $reviewRequest->fresh()->status);
    }

    /**
     * @return array{0: User, 1: Module, 2: Lesson, 3: LessonTopic, 4: InteractiveActivity, 5: Quiz}
     */
    private function contentFixture(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id]);
        $activity = InteractiveActivity::factory()->create(['lesson_topic_id' => $topic->id]);
        $quiz = Quiz::factory()->create(['module_id' => $module->id]);

        return [$admin, $module, $lesson, $topic, $activity, $quiz];
    }

    private function createRevision(Module $module, User $admin, array $snapshot, string $status = 'approved'): ModuleRevision
    {
        return ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => ((int) $module->revisions()->max('revision_number')) + 1,
            'snapshot_payload' => $snapshot,
            'submitted_by' => $admin->id,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function snapshot(
        Module $module,
        Lesson $lesson,
        LessonTopic $topic,
        InteractiveActivity $activity,
        Quiz $quiz,
        string $title,
        ?Lesson $additionalLesson = null,
    ): array {
        $lessons = [[
            'attributes' => ['id' => $lesson->id, 'title' => $title],
            'topics' => [[
                'id' => $topic->id,
                'interactive_activities' => [['id' => $activity->id]],
            ]],
        ]];

        if ($additionalLesson) {
            $lessons[] = [
                'attributes' => ['id' => $additionalLesson->id, 'title' => 'Added lesson'],
                'topics' => [],
            ];
        }

        return [
            'module' => ['id' => $module->id, 'title' => $title],
            'lessons' => $lessons,
            'quizzes' => [['attributes' => ['id' => $quiz->id, 'title' => $title], 'questions' => []]],
        ];
    }

    private function emptySnapshot(Module $module): array
    {
        return [
            'module' => ['id' => $module->id, 'title' => $module->title],
            'lessons' => [],
            'quizzes' => [],
        ];
    }
}
