<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserPermissionUiTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $permissions = ['create users', 'edit users', 'manage permissions'];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole($adminRole);

        return $admin;
    }

    public function test_create_wizard_shows_toggle_sections_without_inline_permission_descriptions(): void
    {
        $this->withoutVite();

        $permission = Permission::findOrCreate('manage users', 'web');
        $permission->description = 'Can manage user records and lifecycle actions.';
        $permission->save();

        $learnerRole = Role::findOrCreate('learner', 'web');
        $learnerRole->syncPermissions(['manage users']);

        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->followingRedirects()
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Role Permissions', false)
            ->assertSee('User Permission Overrides', false)
            ->assertSee('Show', false)
            ->assertDontSee('Can manage user records and lifecycle actions.', false)
            ->assertSee('manage users', false);
    }

    public function test_edit_wizard_shows_toggle_sections_without_inline_permission_descriptions(): void
    {
        $this->withoutVite();

        $permission = Permission::findOrCreate('view reports', 'web');
        $permission->description = 'Can view governance and learner reports.';
        $permission->save();

        $instructorRole = Role::findOrCreate('instructor', 'web');
        $instructorRole->syncPermissions(['view reports']);

        $admin = $this->createAdminUser();
        $target = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $target->assignRole($instructorRole);

        $this->actingAs($admin)
            ->followingRedirects()
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('Role Permissions', false)
            ->assertSee('User Permission Overrides', false)
            ->assertSee('view reports', false)
            ->assertDontSee('Can view governance and learner reports.', false);
    }
}
