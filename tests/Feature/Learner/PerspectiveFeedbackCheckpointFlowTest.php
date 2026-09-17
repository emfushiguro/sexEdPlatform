<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InteractiveCheckpointProgress;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Services\Learning\QuestionEvaluator;
use Mockery;
use Tests\TestCase;

class PerspectiveFeedbackCheckpointFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_guided_perspective_submission_is_completed_without_correctness(): void
    {
        [$learner, $question] = $this->perspectiveFixture();
        $selected = $question->options->first();
        $evaluator = Mockery::mock(QuestionEvaluator::class);
        $evaluator->shouldNotReceive('evaluate');
        $this->app->instance(QuestionEvaluator::class, $evaluator);

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.submit', $question), [
                'answer' => ['pathway' => 'guided', 'option_id' => $selected->id],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('is_correct', null)
            ->assertJsonPath('result.option_id', $selected->id)
            ->assertJsonPath('feedback', $selected->feedback)
            ->assertJsonPath('explanation', $question->explanation);

        $this->assertDatabaseHas('interactive_checkpoint_progress', [
            'user_id' => $learner->id,
            'quiz_question_id' => $question->id,
            'status' => 'completed',
            'is_correct' => null,
            'attempt_count' => 1,
        ]);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_written_perspective_is_preserved_and_never_graded(): void
    {
        [$learner, $question] = $this->perspectiveFixture();
        $text = 'I would listen first and ask what support they want.';

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.submit', $question), [
                'answer' => ['pathway' => 'own', 'perspective_text' => $text],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('is_correct', null)
            ->assertJsonPath('result.perspective_text', $text)
            ->assertJsonPath('feedback', null);

        $progress = InteractiveCheckpointProgress::firstOrFail();
        $this->assertSame($text, $progress->latest_answer['perspective_text']);
        $this->assertNull($progress->is_correct);
    }

    public function test_guided_submission_rejects_an_option_from_another_checkpoint(): void
    {
        [$learner, $question] = $this->perspectiveFixture();
        [, $otherQuestion] = $this->perspectiveFixture();

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.submit', $question), [
                'answer' => ['pathway' => 'guided', 'option_id' => $otherQuestion->options->first()->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer.option_id');

        $this->assertDatabaseMissing('interactive_checkpoint_progress', [
            'user_id' => $learner->id,
            'quiz_question_id' => $question->id,
        ]);
    }

    public function test_written_submission_is_rejected_when_disabled_empty_or_over_limit(): void
    {
        [$learner, $question] = $this->perspectiveFixture([
            'allow_own_perspective' => false,
            'perspective_character_limit' => 10,
        ]);

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.submit', $question), [
                'answer' => ['pathway' => 'own', 'perspective_text' => 'My view'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer.pathway');

        $question->update(['allow_own_perspective' => true]);

        $this->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => ''],
        ])->assertUnprocessable()->assertJsonValidationErrors('answer.perspective_text');

        $this->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => '12345678901'],
        ])->assertUnprocessable()->assertJsonValidationErrors('answer.perspective_text');
    }

    public function test_first_completed_response_and_feedback_snapshot_are_immutable(): void
    {
        [$learner, $question] = $this->perspectiveFixture();
        $selected = $question->options->first();

        $this->actingAs($learner)->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'guided', 'option_id' => $selected->id],
        ])->assertOk();

        $selected->update(['option_text' => 'Edited later', 'feedback' => 'Edited feedback']);
        $selected->delete();

        $this->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => 'Replacement attempt'],
        ])
            ->assertOk()
            ->assertJsonPath('result.option_text', 'Listen first.')
            ->assertJsonPath('feedback', 'Listening creates room for the concern.');

        $progress = InteractiveCheckpointProgress::firstOrFail();
        $this->assertSame(1, $progress->attempt_count);
        $this->assertSame('guided', $progress->latest_answer['pathway']);
    }

    public function test_skipped_perspective_feedback_can_later_be_completed(): void
    {
        [$learner, $question] = $this->perspectiveFixture();

        $this->actingAs($learner)
            ->postJson(route('learner.checkpoints.skip', $question))
            ->assertJsonPath('status', 'skipped');

        $this->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => 'My later reflection'],
        ])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('is_correct', null);
    }

    public function test_perspective_feedback_does_not_change_points_shields_or_quiz_attempts(): void
    {
        [$learner, $question] = $this->perspectiveFixture();
        UserDailyShield::refillFull($learner);
        $shieldsBefore = UserDailyShield::getShields($learner);
        $pointsBefore = (int) $learner->gamification()->value('score');

        $this->actingAs($learner)->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'guided', 'option_id' => $question->options->first()->id],
        ])->assertOk();

        $this->assertSame($shieldsBefore, UserDailyShield::getShields($learner->refresh()));
        $this->assertSame($pointsBefore, (int) $learner->gamification()->value('score'));
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    private function perspectiveFixture(array $overrides = []): array
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive_checkpoint',
            'interactive_config' => ['placement' => 'between_topics'],
        ]);
        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $question = QuizQuestion::create(array_merge([
            'checkpoint_topic_id' => $topic->id,
            'question_text' => '<p>How would you respond?</p>',
            'question_type' => 'perspective_feedback',
            'points' => 0,
            'order' => 1,
            'allow_own_perspective' => true,
            'perspective_prompt' => 'Share your perspective.',
            'perspective_character_limit' => 1000,
            'reflection_guide' => 'Consider boundaries and effects.',
            'explanation' => 'Respect and support can work together.',
        ], $overrides));
        $question->options()->createMany([
            ['option_text' => 'Listen first.', 'feedback' => 'Listening creates room for the concern.', 'is_correct' => false, 'order' => 0],
            ['option_text' => 'Decide for them.', 'feedback' => 'Support should not replace their agency.', 'is_correct' => false, 'order' => 1],
        ]);

        return [$learner, $question->refresh()->load('options')];
    }
}
