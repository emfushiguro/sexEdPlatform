<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminModulesRealtimeFilterTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_module_filters_compose_owner_status_and_search(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'draft',
            'title' => 'Alpha Draft Instructor Module',
        ]);

        Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
            'title' => 'Alpha Published Instructor Module',
        ]);

        Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'is_published' => false,
            'current_review_status' => 'draft',
            'title' => 'Alpha Draft Platform Module',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.modules.index', [
            'search' => 'Alpha Draft',
            'status' => 'draft',
            'owner_type' => 'instructor',
        ]));

        $response->assertOk();
        $response->assertSee('Alpha Draft Instructor Module');
        $response->assertDontSee('Alpha Published Instructor Module');
        $response->assertDontSee('Alpha Draft Platform Module');
        $response->assertSee('data-testid="admin-modules-search-input"', false);
        $response->assertSee('x-model.debounce.250ms="searchTerm"', false);
        $response->assertSee('@submit.prevent', false);
    }

    public function test_admin_module_filter_queries_persist_across_pagination_links(): void
    {
        $admin = $this->createUser('admin');

        for ($i = 1; $i <= 13; $i++) {
            Module::factory()->create([
                'created_by' => $admin->id,
                'content_owner_type' => 'admin',
                'is_published' => false,
                'current_review_status' => 'draft',
                'title' => 'Alpha Query Module ' . $i,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.modules.index', [
            'search' => 'Alpha Query',
            'status' => 'draft',
            'owner_type' => 'platform',
        ]));

        $response->assertOk();
        $response->assertSee('search=Alpha', false);
        $response->assertSee('status=draft', false);
        $response->assertSee('owner_type=platform', false);
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