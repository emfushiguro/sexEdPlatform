<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminModulesIndexSegmentationTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_scope_all_includes_platform_and_instructor_modules(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $platformModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Module',
        ]);

        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Module',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.modules.index', ['scope' => 'all']));

        $response->assertOk();

        $modules = $response->viewData('modules')->getCollection()->pluck('id');

        $this->assertTrue($modules->contains($platformModule->id));
        $this->assertTrue($modules->contains($instructorModule->id));
    }

    public function test_admin_scope_filters_platform_instructor_and_archived(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $platformModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Scope Module',
        ]);

        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Scope Module',
        ]);

        $archivedInstructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Archived Instructor Scope Module',
        ]);
        $archivedInstructorModule->delete();

        $platformResponse = $this->actingAs($admin)
            ->get(route('admin.modules.index', ['scope' => 'platform']));
        $platformIds = $platformResponse->viewData('modules')->getCollection()->pluck('id');

        $this->assertTrue($platformIds->contains($platformModule->id));
        $this->assertFalse($platformIds->contains($instructorModule->id));

        $instructorResponse = $this->actingAs($admin)
            ->get(route('admin.modules.index', ['scope' => 'instructor']));
        $instructorIds = $instructorResponse->viewData('modules')->getCollection()->pluck('id');

        $this->assertTrue($instructorIds->contains($instructorModule->id));
        $this->assertFalse($instructorIds->contains($platformModule->id));

        $archivedResponse = $this->actingAs($admin)
            ->get(route('admin.modules.index', ['status' => 'archived']));
        $archivedIds = $archivedResponse->viewData('modules')->getCollection()->pluck('id');

        $this->assertTrue($archivedIds->contains($archivedInstructorModule->id));
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
