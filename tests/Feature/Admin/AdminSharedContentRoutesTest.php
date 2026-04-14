<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedContentRoutesTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_shared_content_routes_are_available(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('admin.modules.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.lessons.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.quizzes.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.enrollments.index'))->assertOk();
    }

    public function test_admin_topics_create_route_is_available_for_authorized_admin(): void
    {
        $admin = $this->createAdmin();
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.topics.create', ['lesson' => $lesson->id]));

        $response->assertOk();
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
}
