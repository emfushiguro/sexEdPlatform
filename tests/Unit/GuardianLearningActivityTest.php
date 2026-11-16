<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Services\GuardianLearningActivityService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Illuminate\Foundation\Testing\TestCase;

class GuardianLearningActivityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_only_recorded_learning_dates_and_safe_login_labels_are_shown(): void
    {
        $child = Mockery::mock(User::class)->makePartial();
        $progress = new UserProgress(['last_accessed_at' => '2026-11-14 10:00:00', 'completed_at' => '2026-11-14 11:00:00']);
        $progress->setRelation('lesson', null)->setRelation('module', null);
        $completion = new UserProgress(['completed_at' => '2026-11-13 11:00:00']);
        $completion->setRelation('lesson', null)->setRelation('module', null);
        $child->shouldReceive('userProgress->with->where->orderByRaw->limit->get')->once()->andReturn(new Collection([$progress, $completion]));
        $login = (new ActivityLog())->forceFill(['description' => 'Private text', 'metadata' => ['secret' => 'hidden'], 'created_at' => '2026-11-15 09:00:00']);
        $query = Mockery::mock();
        $child->shouldReceive('activityLogs')->once()->andReturn($query);
        $query->shouldReceive('whereIn')->with('activity_type', ['login', 'lesson_viewed'])->once()->andReturnSelf();
        $query->shouldReceive('latest->limit->get')->once()->andReturn(new Collection([$login]));

        $child->shouldNotReceive('quizAttempts');
        $events = (new GuardianLearningActivityService())->forChild($child, false);

        $this->assertSame(['Signed in', 'Completed learning content', 'Viewed learning content', 'Completed learning content'], $events->pluck('label')->all());
        $this->assertStringNotContainsString('Private text', json_encode($events));
        $this->assertStringNotContainsString('secret', json_encode($events));
    }

    public function test_login_event_records_only_learners_and_views_record_the_authorized_lesson(): void
    {
        $learner = Mockery::mock(User::class)->makePartial();
        $learner->shouldReceive('isLearner')->andReturnTrue();
        $learner->shouldReceive('activityLogs->create')->once()->with(['activity_type' => 'login', 'metadata' => null]);
        $learner->shouldReceive('activityLogs->create')->once()->with(['activity_type' => 'lesson_viewed', 'metadata' => ['lesson_id' => 7, 'lesson_title' => 'Learning topic']]);
        $guardian = Mockery::mock(User::class)->makePartial();
        $guardian->shouldReceive('isLearner')->andReturnFalse();
        $guardian->shouldNotReceive('activityLogs');
        $service = new GuardianLearningActivityService();
        Event::dispatch(new Login('web', $learner, false));
        Event::dispatch(new Login('web', $guardian, false));
        $service->recordLessonView($learner, (new Lesson())->forceFill(['id' => 7, 'title' => 'Learning topic']));
        $this->assertTrue(true);
    }

    public function test_shared_quiz_activity_uses_saved_completion_without_exposing_answers(): void
    {
        $child = Mockery::mock(User::class)->makePartial();
        $child->shouldReceive('userProgress->with->where->orderByRaw->limit->get')->andReturn(new Collection());
        $child->shouldReceive('activityLogs->whereIn->latest->limit->get')->andReturn(new Collection());
        $attempt = (new QuizAttempt())->forceFill(['completed_at' => '2026-11-15 10:00:00', 'answers' => ['private answer'], 'score' => 90]);
        $attempt->setRelation('quiz', null);
        $child->shouldReceive('quizAttempts->with->whereNotNull->latest->limit->get')->once()->andReturn(new Collection([$attempt]));
        $events = (new GuardianLearningActivityService())->forChild($child, true);
        $this->assertSame(['Completed quiz'], $events->pluck('label')->all());
        $this->assertStringNotContainsString('private answer', json_encode($events));
        $this->assertStringNotContainsString('score', json_encode($events));
    }
}
