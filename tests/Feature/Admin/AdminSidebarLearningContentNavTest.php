<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSidebarLearningContentNavTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_sidebar_contains_learning_content_links(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee(route('admin.modules.index'), false);
        $response->assertSee(route('admin.lessons.index'), false);
        $response->assertDontSee(route('admin.topics.create'), false);
        $response->assertSee(route('admin.quizzes.index'), false);
        $response->assertSee(route('admin.enrollments.index'), false);
        $response->assertSee(route('admin.learners.index'), false);
    }

    public function test_admin_learners_route_supports_platform_and_instructor_scopes(): void
    {
        $admin = $this->createAdmin();
        $instructor = $this->createInstructor();

        $platformModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);
        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
        ]);

        $platformLearner = $this->createLearner('Platform Learner');
        $instructorLearner = $this->createLearner('Instructor Learner');
        $unscopedLearner = $this->createLearner('Unscoped Learner');

        ModuleEnrollment::factory()->create([
            'user_id' => $platformLearner->id,
            'module_id' => $platformModule->id,
            'status' => 'approved',
        ]);

        ModuleEnrollment::factory()->create([
            'user_id' => $instructorLearner->id,
            'module_id' => $instructorModule->id,
            'status' => 'approved',
        ]);

        $allLearners = $this->actingAs($admin)->get(route('admin.learners.index'));
        $allLearners->assertOk();
        $allLearners->assertSee('Platform Learner');
        $allLearners->assertSee('Instructor Learner');
        $allLearners->assertSee('Unscoped Learner');

        $platformScope = $this->actingAs($admin)->get(route('admin.learners.index', [
            'learner_scope' => 'platform',
        ]));

        $platformScope->assertOk();
        $platformScope->assertSee('Platform Learner');
        $platformScope->assertDontSee('Instructor Learner');
        $platformScope->assertDontSee('Unscoped Learner');

        $instructorScope = $this->actingAs($admin)->get(route('admin.learners.index', [
            'learner_scope' => 'instructor',
        ]));

        $instructorScope->assertOk();
        $instructorScope->assertSee('Instructor Learner');
        $instructorScope->assertDontSee('Platform Learner');
        $instructorScope->assertDontSee('Unscoped Learner');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        return $instructor;
    }

    private function createLearner(string $name): User
    {
        $learner = User::factory()->create([
            'name' => $name,
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        return $learner;
    }
}
