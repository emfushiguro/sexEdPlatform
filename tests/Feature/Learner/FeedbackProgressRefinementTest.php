<?php

namespace Tests\Feature\Learner;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Services\LearnerModuleCompletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Dedicated in-memory schema: never refresh or seed an existing database.
class FeedbackProgressRefinementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'feedback_memory', 'database.connections.feedback_memory' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach (['users', 'modules', 'lessons', 'lesson_topics', 'quizzes', 'quiz_attempts', 'module_enrollments', 'user_progress', 'lesson_topic_progress', 'interactive_checkpoint_progress', 'interactive_activities', 'interactive_activity_progress', 'certificates'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->timestamps();
                $t->softDeletes();
                foreach (['user_id', 'module_id', 'lesson_id', 'lesson_topic_id', 'quiz_id', 'final_quiz_id', 'attempt_limit', 'checkpoint_block_uuid', 'interactive_activity_id', 'revision', 'activity_revision'] as $column) $t->integer($column)->nullable();
                foreach (['type', 'status', 'placement'] as $column) $t->string($column)->nullable();
                foreach (['completed', 'passed', 'is_published', 'is_active'] as $column) $t->boolean($column)->default(false);
                $t->integer('completion_percentage')->default(0);
                $t->timestamp('completed_at')->nullable();
            });
        }
        DB::table('users')->insert(['id' => 1]);
        DB::table('modules')->insert(['id' => 1]);
        DB::table('lessons')->insert(['id' => 1, 'module_id' => 1, 'is_published' => true]);
        DB::table('module_enrollments')->insert(['user_id' => 1, 'module_id' => 1, 'status' => 'approved']);
        DB::table('user_progress')->insert(['user_id' => 1, 'module_id' => 1, 'lesson_id' => 1, 'completed' => true]);
        DB::table('lesson_topics')->insert([
            ['id' => 1, 'lesson_id' => 1, 'type' => 'text'],
            ['id' => 2, 'lesson_id' => 1, 'type' => 'interactive_checkpoint'],
            ['id' => 3, 'lesson_id' => 1, 'type' => 'interactive'],
        ]);
        DB::table('lesson_topic_progress')->insert(['user_id' => 1, 'lesson_topic_id' => 1, 'completed' => true]);
    }

    public function test_optional_interactions_do_not_block_certificates(): void
    {
        $this->assertNull(app(LearnerModuleCompletionService::class)->completionBlockerReason(User::find(1), Module::find(1)));
    }

    public function test_module_completion_is_recorded_and_rewarded_once_when_optional_items_are_unattempted(): void
    {
        $points = \Mockery::mock(\App\Services\GamificationService::class);
        $points->shouldReceive('awardConfiguredPoints')->once()->with(\Mockery::type(User::class), 'module_completed')->andReturn(25);
        $this->app->instance(\App\Services\GamificationService::class, $points);
        $service = app(LearnerModuleCompletionService::class);
        $this->assertSame(25, $service->recordCompletion(User::find(1), Module::find(1)));
        $this->assertNull($service->recordCompletion(User::find(1), Module::find(1)));
        $this->assertNotNull(DB::table('module_enrollments')->value('completed_at'));
    }

    public function test_module_feedback_claims_through_a_protected_form_and_views_existing_certificates(): void
    {
        $service = app(LearnerModuleCompletionService::class);
        $feedback = $service->moduleFeedback(User::find(1), Module::find(1));
        $this->assertSame('Claim certificate', $feedback['actions'][0]['label']);
        $html = \Illuminate\Support\Facades\Blade::render('<x-learner.learning-feedback :initial-feedback="$feedback" />', compact('feedback'));
        $this->assertStringContainsString('method="POST"', $html);
        $this->assertStringContainsString(route('learner.certificates.check', 1), $html);
        $this->assertStringContainsString('name="_token"', $html);
        DB::table('certificates')->insert(['id' => 1, 'user_id' => 1, 'module_id' => 1]);
        $feedback = $service->moduleFeedback(User::find(1), Module::find(1));
        $this->assertSame('View certificate', $feedback['actions'][0]['label']);
        $this->assertSame(route('learner.certificates.show', 1), $feedback['actions'][0]['url']);
    }

    public function test_skipped_checkpoint_and_activity_are_visible_as_resolved_without_topic_progress_rows(): void
    {
        DB::table('interactive_checkpoint_progress')->insert(['user_id' => 1, 'lesson_topic_id' => 2, 'status' => 'skipped', 'completed_at' => now()]);
        DB::table('interactive_activities')->insert(['id' => 1, 'lesson_topic_id' => 3, 'revision' => 1, 'placement' => 'between_topics']);
        DB::table('interactive_activity_progress')->insert(['user_id' => 1, 'interactive_activity_id' => 1, 'activity_revision' => 1, 'status' => 'skipped']);
        $ids = app(LearnerModuleCompletionService::class)->completedTopicIds(User::find(1), Module::find(1)->lessons->flatMap->topics);
        $this->assertEqualsCanonicalizing([1, 2, 3], $ids->all());
    }

    public function test_saved_quiz_progress_is_available_for_every_lesson_and_ignores_unfinished_attempts(): void
    {
        DB::table('quizzes')->insert([
            ['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'attempt_limit' => 2],
            ['id' => 2, 'lesson_id' => 2, 'module_id' => 1, 'attempt_limit' => 1],
        ]);
        DB::table('quiz_attempts')->insert([
            ['user_id' => 1, 'quiz_id' => 1, 'passed' => true, 'completed_at' => now()],
            ['user_id' => 1, 'quiz_id' => 2, 'passed' => false, 'completed_at' => null],
        ]);
        $progress = app(LearnerModuleCompletionService::class)->quizProgress(User::find(1), Quiz::all());
        $this->assertTrue($progress[1]['resolved']);
        $this->assertTrue($progress[1]['passed']);
        $this->assertFalse($progress[2]['attempted']);
        $this->assertFalse($progress[2]['resolved']);
    }

    public function test_failed_finished_quiz_is_recorded_but_only_resolves_at_the_attempt_limit(): void
    {
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'attempt_limit' => 2, 'is_active' => true]);
        DB::table('quiz_attempts')->insert(['user_id' => 1, 'quiz_id' => 1, 'passed' => false, 'completed_at' => now()]);
        $service = app(LearnerModuleCompletionService::class);
        $this->assertTrue($service->quizProgress(User::find(1), Quiz::all())[1]['attempted']);
        $this->assertFalse($service->quizProgress(User::find(1), Quiz::all())[1]['resolved']);
        $this->assertSame('Complete all lesson quizzes before submitting feedback.', $service->completionBlockerReason(User::find(1), Module::find(1)));
        DB::table('quiz_attempts')->insert(['user_id' => 1, 'quiz_id' => 1, 'passed' => false, 'completed_at' => now()]);
        $this->assertTrue($service->quizProgress(User::find(1), Quiz::all())[1]['resolved']);
        $this->assertNull($service->completionBlockerReason(User::find(1), Module::find(1)));
    }

    public function test_lesson_percentage_alone_does_not_bypass_required_quizzes(): void
    {
        DB::table('module_enrollments')->update(['completion_percentage' => 100]);
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true]);
        $this->assertSame('Complete all lesson quizzes before submitting feedback.', app(LearnerModuleCompletionService::class)->completionBlockerReason(User::find(1), Module::find(1)));
    }

    public function test_module_completion_stamp_does_not_bypass_undone_required_topics(): void
    {
        DB::table('module_enrollments')->update(['completed_at' => now(), 'completion_percentage' => 100]);
        DB::table('lesson_topic_progress')->update(['completed' => false]);
        $this->assertSame('Complete all lesson topics before submitting feedback.', app(LearnerModuleCompletionService::class)->completionBlockerReason(User::find(1), Module::find(1)));
    }
}
