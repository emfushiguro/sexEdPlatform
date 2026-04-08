<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserRelationshipTransparencyTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $permissions = ['view users', 'manage user relationships'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_user_profile_shows_parent_child_transparency_panel(): void
    {
        $this->withoutVite();

        $admin = $this->createAdminUser();
        $parent = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $child = User::factory()->create(['role' => 'learner', 'status' => 'active']);

        ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'relationship_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $child))
            ->assertOk()
            ->assertSee('Parent-Child Transparency', false)
            ->assertSee($parent->name, false)
            ->assertSee('Learner-To-Instructor Lineage', false)
            ->assertSee('Role Transition Timeline', false);
    }
}
