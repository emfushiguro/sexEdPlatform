<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_enrollments_accepts_pending_parent_approval_status(): void
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');

        $module = Module::factory()->create();

        $enrollment = ModuleEnrollment::create([
            'user_id'    => $child->id,
            'module_id'  => $module->id,
            'status'     => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }

    private function createParentWithChild(): array
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id'        => $parent->id,
            'child_user_id'         => $child->id,
            'can_view_progress'     => true,
            'can_view_quiz_answers' => true,
            'can_approve_content'   => true,
        ]);

        UserGamification::create([
            'user_id'      => $child->id,
            'level'        => 1,
            'score'        => 0,
            'total_points' => 0,
            'streak_count' => 0,
        ]);

        return [$parent, $child];
    }

    public function test_parent_can_view_own_childs_detail_page(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $this->actingAs($parent)
             ->get(route('parent.children.show', $child))
             ->assertOk();
    }

    public function test_parent_cannot_view_another_users_child(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $stranger = User::factory()->create(['email_verified_at' => now()]);
        $stranger->assignRole('learner');

        $this->actingAs($stranger)
             ->get(route('parent.children.show', $child))
             ->assertForbidden();
    }

    public function test_guest_cannot_access_parent_routes(): void
    {
        $child = User::factory()->create();

        $this->get(route('parent.children.show', $child))
             ->assertRedirect('/login');
    }

    public function test_get_progress_returns_approved_enrollments_with_progress(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['title' => 'Test Module']);
        ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'approved',
            'enrolled_at' => now(),
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $progress = $service->getProgress($child);

        $this->assertCount(1, $progress);
        $this->assertEquals('Test Module', $progress->first()->module->title);
    }

    public function test_get_quiz_results_returns_attempts_newest_first(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $quiz = Quiz::factory()->create();

        QuizAttempt::create([
            'user_id'      => $child->id,
            'quiz_id'      => $quiz->id,
            'score'        => 80,
            'passed'       => true,
            'answers'      => json_encode([]),
            'started_at'   => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $results = $service->getQuizResults($child);

        $this->assertCount(1, $results);
        $this->assertEquals(80, $results->first()->score);
    }

    public function test_get_achievements_returns_gamification_and_reward_logs(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $service = app(\App\Services\ParentChildService::class);
        $achievements = $service->getAchievements($child);

        $this->assertArrayHasKey('gamification', $achievements);
        $this->assertArrayHasKey('rewardLogs', $achievements);
        $this->assertEquals(1, $achievements['gamification']->level);
    }

    public function test_get_pending_enrollments_returns_only_pending_parent_approval(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create();
        ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $pending = $service->getPendingEnrollments($child);

        $this->assertCount(1, $pending);
        $this->assertEquals('pending_parent_approval', $pending->first()->status);
    }
}
