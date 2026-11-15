<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\GamificationService;
use App\Services\Learning\CompletionRewardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

class CompletionRewardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->softDeletes();
        });
        Schema::create('learning_completion_rewards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('reason');
            $t->unsignedBigInteger('subject_id');
            $t->timestamps();
            $t->unique(['user_id', 'reason', 'subject_id']);
        });
        DB::table('users')->insert(['id' => 1]);
    }

    public function test_topic_and_lesson_rewards_are_awarded_only_once(): void
    {
        $user = User::findOrFail(1);
        $points = Mockery::mock(GamificationService::class);
        $points->shouldReceive('awardConfiguredPoints')->once()->with($user, 'topic_complete')->andReturn(10);
        $points->shouldReceive('awardConfiguredPoints')->once()->with($user, 'lesson_complete')->andReturn(15);
        $rewards = new CompletionRewardService($points);
        $this->assertSame(10, $rewards->award($user, 'topic_complete', 2));
        $this->assertSame(0, $rewards->award($user, 'topic_complete', 2));
        $this->assertSame(15, $rewards->award($user, 'lesson_complete', 2));
        $this->assertSame(0, $rewards->award($user, 'lesson_complete', 2));
        $this->assertSame(2, DB::table('learning_completion_rewards')->count());
    }

    public function test_failed_award_does_not_consume_the_reward(): void
    {
        $points = Mockery::mock(GamificationService::class);
        $points->shouldReceive('awardConfiguredPoints')->once()->andThrow(new \RuntimeException('test failure'));
        try {
            (new CompletionRewardService($points))->award(User::findOrFail(1), 'topic_complete', 2);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('test failure', $e->getMessage());
        }
        $this->assertSame(0, DB::table('learning_completion_rewards')->count());
    }

    public function test_controller_retries_and_incomplete_cycles_do_not_repeat_rewards(): void
    {
        Schema::create('lesson_topics', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lesson_id');
            $t->string('type');
            $t->integer('order');
        });
        Schema::create('lesson_topic_progress', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('lesson_topic_id');
            $t->boolean('completed');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_topic_id']);
        });
        Schema::create('user_progress', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('module_id');
            $t->unsignedBigInteger('lesson_id');
            $t->boolean('completed');
            $t->integer('progress_percentage');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });
        DB::table('lesson_topics')->insert(['id' => 2, 'lesson_id' => 1, 'type' => 'text', 'order' => 1]);
        $user = User::findOrFail(1);
        \Illuminate\Support\Facades\Auth::setUser($user);
        $module = (new \App\Models\Module)->forceFill(['id' => 1]);
        $lesson = (new \App\Models\Lesson)->forceFill(['id' => 1, 'module_id' => 1, 'is_published' => true]);
        $lesson->setRelation('module', $module);
        $topic = \App\Models\LessonTopic::findOrFail(2)->setRelation('lesson', $lesson);
        $points = Mockery::mock(GamificationService::class);
        $points->shouldReceive('awardConfiguredPoints')->once()->with($user, 'topic_complete')->andReturn(10);
        $points->shouldReceive('awardConfiguredPoints')->once()->with($user, 'lesson_complete')->andReturn(15);
        $points->shouldReceive('updateStreak')->with($user)->andReturn(0);
        $access = Mockery::mock(\App\Services\Modules\ModuleAccessService::class);
        $access->shouldReceive('assertCanLearn')->with($user, $module);
        $controller = new \App\Http\Controllers\Learner\LessonController($points,
            Mockery::mock(\App\Services\Learning\InteractiveActivities\InteractiveActivityProgressService::class),
            Mockery::mock(\App\Services\Learning\InteractiveActivities\InteractiveActivityPresenter::class), $access);
        $controller->completeTopic($topic);
        $this->assertSame(25, session('points_earned'));
        $controller->completeTopic($topic);
        $this->assertSame(0, session('points_earned'));
        $controller->uncompleteTopic($topic);
        $controller->complete($lesson);
        $this->assertSame(0, session('points_earned'));
        $controller->completeTopic($topic);
        $this->assertSame(0, session('points_earned'));
        $this->assertSame(2, DB::table('learning_completion_rewards')->count());
        $this->assertSame(1, DB::table('user_progress')->count());
        $this->assertTrue((bool) DB::table('user_progress')->value('completed'));
    }
}
