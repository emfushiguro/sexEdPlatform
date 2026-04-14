<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminAllModulesPageTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_all_modules_page_lists_platform_and_instructor_modules(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Table Module',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);

        Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Pending Module',
            'is_published' => false,
            'current_review_status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('All Modules');
        $response->assertSee('Platform Table Module');
        $response->assertSee('Instructor Pending Module');
        $response->assertSee('Conscious Connections Team');
    }

    public function test_admin_all_modules_page_filters_scope_status_and_search(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Scope Module',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);

        Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Pending Scope Module',
            'is_published' => false,
            'current_review_status' => 'submitted',
        ]);

        $platformScope = $this->actingAs($admin)->get(route('admin.modules.index', [
            'scope' => 'platform',
        ]));
        $platformScope->assertOk();
        $platformScope->assertSee('Platform Scope Module');
        $platformScope->assertDontSee('Instructor Pending Scope Module');

        $pendingStatus = $this->actingAs($admin)->get(route('admin.modules.index', [
            'status' => 'pending',
        ]));
        $pendingStatus->assertOk();
        $pendingStatus->assertSee('Instructor Pending Scope Module');
        $pendingStatus->assertDontSee('Platform Scope Module');

        $searchResults = $this->actingAs($admin)->get(route('admin.modules.index', [
            'search' => 'Pending Scope',
        ]));
        $searchResults->assertOk();
        $searchResults->assertSee('Instructor Pending Scope Module');
        $searchResults->assertDontSee('Platform Scope Module');
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
