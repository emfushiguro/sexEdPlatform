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
}
