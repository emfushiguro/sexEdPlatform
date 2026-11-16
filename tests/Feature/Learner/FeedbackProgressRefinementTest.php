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
                foreach (['user_id', 'module_id', 'lesson_id', 'lesson_topic_id', 'quiz_id', 'final_quiz_id', 'attempt_limit', 'checkpoint_block_uuid', 'interactive_activity_id', 'revision', 'activity_revision', 'order'] as $column) $t->integer($column)->nullable();
                foreach (['type', 'status', 'placement'] as $column) $t->string($column)->nullable();
                foreach (['completed', 'passed', 'is_published', 'is_active'] as $column) $t->boolean($column)->default(false);
                $t->integer('completion_percentage')->default(0);
                $t->integer('progress_percentage')->default(0);
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

    public function test_next_lesson_is_blocked_until_the_previous_lesson_quiz_is_resolved(): void
    {
        DB::table('lessons')->where('id', 1)->update(['order' => 1]);
        DB::table('lessons')->insert(['id' => 2, 'module_id' => 1, 'order' => 2, 'is_published' => true]);
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true, 'attempt_limit' => 2]);
        $service = app(LearnerModuleCompletionService::class);
        $this->assertFalse($service->lessonReadyToAdvance(User::find(1), \App\Models\Lesson::find(1)));
        $this->assertSame(1, $service->firstIncompleteLessonBefore(User::find(1), \App\Models\Lesson::find(2))->id);
        DB::table('quiz_attempts')->insert(['user_id' => 1, 'quiz_id' => 1, 'passed' => false, 'completed_at' => now()]);
        $this->assertFalse($service->lessonReadyToAdvance(User::find(1), \App\Models\Lesson::find(1)));
        DB::table('quiz_attempts')->insert(['user_id' => 1, 'quiz_id' => 1, 'passed' => true, 'completed_at' => now()]);
        $this->assertTrue($service->lessonReadyToAdvance(User::find(1), \App\Models\Lesson::find(1)));
        $this->assertNull($service->firstIncompleteLessonBefore(User::find(1), \App\Models\Lesson::find(2)));
    }

    public function test_lesson_completion_action_prioritizes_the_required_quiz(): void
    {
        DB::table('lessons')->where('id', 1)->update(['order' => 1]);
        DB::table('lessons')->insert(['id' => 2, 'module_id' => 1, 'order' => 2, 'is_published' => true]);
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true]);
        $action = app(LearnerModuleCompletionService::class)->lessonForwardAction(User::find(1), \App\Models\Lesson::find(1));
        $this->assertSame('Take lesson quiz', $action['label']);
        $this->assertSame(route('learner.lessons.show', ['lesson' => 1, 'quiz' => 1]), $action['url']);
    }

    public function test_direct_next_lesson_get_and_completion_posts_redirect_to_the_pending_quiz(): void
    {
        DB::table('lessons')->where('id', 1)->update(['order' => 1]);
        DB::table('lessons')->insert(['id' => 2, 'module_id' => 1, 'order' => 2, 'is_published' => true]);
        DB::table('lesson_topics')->insert(['id' => 4, 'lesson_id' => 2, 'type' => 'text']);
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true]);
        $controller = $this->completionController();
        $expected = route('learner.lessons.show', ['lesson' => 1, 'quiz' => 1]);
        $this->assertSame($expected, $controller->show(\App\Models\Lesson::find(2))->getTargetUrl());
        $this->assertSame($expected, $controller->complete(\App\Models\Lesson::find(2))->getTargetUrl());
        $this->assertSame($expected, $controller->completeTopic(\App\Models\LessonTopic::find(4))->getTargetUrl());
        $this->assertSame(0, DB::table('user_progress')->where('lesson_id', 2)->count());
    }

    public function test_inline_quiz_does_not_open_before_required_topics_are_complete(): void
    {
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true]);
        DB::table('lesson_topic_progress')->update(['completed' => false]);
        request()->query->set('quiz', 1);
        $response = $this->completionController()->show(\App\Models\Lesson::find(1));
        $this->assertSame(route('learner.lessons.show', 1), $response->getTargetUrl());
        $this->assertSame('Complete the lesson topics before taking this quiz.', session('error'));
    }

    public function test_direct_quiz_start_and_submission_enforce_topics_and_previous_quizzes(): void
    {
        DB::table('lessons')->where('id', 1)->update(['order' => 1]);
        DB::table('lessons')->insert(['id' => 2, 'module_id' => 1, 'order' => 2, 'is_published' => true]);
        DB::table('quizzes')->insert([
            ['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true],
            ['id' => 2, 'lesson_id' => 2, 'module_id' => 1, 'is_active' => true],
        ]);
        DB::table('lesson_topic_progress')->update(['completed' => false]);
        \Illuminate\Support\Facades\Auth::setUser(User::find(1));
        $access = \Mockery::mock(\App\Services\Modules\ModuleAccessService::class);
        $access->shouldReceive('assertCanLearn')->andReturnNull();
        $controller = new \App\Http\Controllers\Learner\QuizController(
            \Mockery::mock(\App\Services\GamificationService::class),
            \Mockery::mock(\App\Services\SubscriptionService::class),
            app(LearnerModuleCompletionService::class),
            \Mockery::mock(\App\Services\Learning\QuestionEvaluator::class), $access);
        foreach ([1, 2] as $quizId) {
            foreach (['start', 'submit'] as $method) {
                try {
                    $quiz = Quiz::find($quizId);
                    $method === 'start' ? $controller->start($quiz) : $controller->submit(new \Illuminate\Http\Request, $quiz);
                    $this->fail('A locked quiz must not open or accept a submission.');
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) {
                    $this->assertSame(403, $error->getStatusCode());
                }
            }
        }
        $this->assertSame(0, DB::table('quiz_attempts')->count());
    }

    public function test_topic_completion_uses_the_centered_manager_with_actual_points(): void
    {
        DB::table('lesson_topics')->insert(['id' => 4, 'lesson_id' => 1, 'type' => 'text', 'order' => 2]);
        DB::table('lesson_topic_progress')->update(['completed' => false]);
        DB::table('user_progress')->update(['completed' => false]);
        $this->completionController()->completeTopic(\App\Models\LessonTopic::find(1));
        $this->assertSame('topic_completed', session('learning_feedback.type'));
        $this->assertSame(10, session('learning_feedback.pointsAwarded'));
        $this->assertTrue(session('learning_feedback.replacesPointsToast'));
        $this->assertSame(session('success'), session('learning_feedback.message'));
        $this->assertNull(session('learning_audio_event'));
    }

    public function test_finishing_topics_points_to_quiz_instead_of_the_next_lesson(): void
    {
        DB::table('lessons')->where('id', 1)->update(['order' => 1]);
        DB::table('lessons')->insert(['id' => 2, 'module_id' => 1, 'order' => 2, 'is_published' => true]);
        DB::table('quizzes')->insert(['id' => 1, 'lesson_id' => 1, 'module_id' => 1, 'is_active' => true]);
        DB::table('lesson_topic_progress')->update(['completed' => false]);
        DB::table('user_progress')->update(['completed' => false]);
        $this->completionController()->completeTopic(\App\Models\LessonTopic::find(1));
        $this->assertSame('Lesson topics complete', session('learning_feedback.title'));
        $this->assertSame('Take lesson quiz', session('learning_feedback.actions.0.label'));
        $this->assertSame(route('learner.lessons.show', ['lesson' => 1, 'quiz' => 1]), session('learning_feedback.actions.0.url'));
        $this->assertSame(25, session('learning_feedback.pointsAwarded'));
        $this->assertSame(session('points_earned'), session('learning_feedback.pointsAwarded'));
    }

    public function test_topic_manager_replaces_the_old_points_toast_in_the_lesson_layout(): void
    {
        \Illuminate\Support\Facades\Auth::setUser(User::find(1)->setRelation('gamification', null)->setRelation('roles', collect()));
        $entitlements = \Mockery::mock(\App\Services\EntitlementService::class);
        $entitlements->shouldReceive('canAccessFeature')->andReturn(true);
        $this->app->instance(\App\Services\EntitlementService::class, $entitlements);
        Schema::create('user_daily_shields', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->date('date'); $t->integer('shields_remaining'); $t->timestamps();
        });
        DB::table('user_daily_shields')->insert(['user_id' => 1, 'date' => today()->toDateString(), 'shields_remaining' => 3]);
        $gamification = \Mockery::mock(\App\Services\GamificationService::class);
        $gamification->shouldReceive('shieldRefillCost')->andReturn(25);
        $gamification->shouldReceive('shieldFullRefillTarget')->andReturn(3);
        $this->app->instance(\App\Services\GamificationService::class, $gamification);
        $suggestions = \Mockery::mock(\App\Services\Chat\ChatSuggestionCatalog::class);
        $suggestions->shouldReceive('forUser')->andReturn([]);
        $this->app->instance(\App\Services\Chat\ChatSuggestionCatalog::class, $suggestions);
        $feedback = ['id' => 'topic:1', 'sourceId' => 'topic:1', 'type' => 'topic_completed',
            'message' => 'You completed this topic and earned 25 points.', 'pointsAwarded' => 25,
            'replacesSuccessToast' => true, 'replacesPointsToast' => true];
        session()->put(['learning_feedback' => $feedback, 'points_earned' => 25, 'success' => $feedback['message']]);
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag);
        $html = \Illuminate\Support\Facades\Blade::render("@extends('layouts.learner-fullscreen') @section('content') Test @endsection");
        $this->assertStringContainsString('topic_completed', $html);
        $this->assertStringNotContainsString('window.toast.pointsEarned(25)', $html);
        $this->assertStringNotContainsString('window.toast.success(', $html);
    }

    private function completionController(): \App\Http\Controllers\Learner\LessonController
    {
        \Illuminate\Support\Facades\Auth::setUser(User::find(1));
        Schema::create('learning_completion_rewards', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->integer('subject_id'); $t->string('reason'); $t->timestamps();
            $t->unique(['user_id', 'subject_id', 'reason']);
        });
        $points = \Mockery::mock(\App\Services\GamificationService::class);
        $points->shouldReceive('awardConfiguredPoints')->with(\Mockery::type(User::class), 'topic_complete')->andReturn(10);
        $points->shouldReceive('awardConfiguredPoints')->with(\Mockery::type(User::class), 'lesson_complete')->andReturn(15);
        $points->shouldReceive('updateStreak')->andReturn(0);
        $access = \Mockery::mock(\App\Services\Modules\ModuleAccessService::class);
        $access->shouldReceive('assertCanLearn')->andReturnNull();
        return new \App\Http\Controllers\Learner\LessonController($points,
            \Mockery::mock(\App\Services\Learning\InteractiveActivities\InteractiveActivityProgressService::class),
            \Mockery::mock(\App\Services\Learning\InteractiveActivities\InteractiveActivityPresenter::class), $access);
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
