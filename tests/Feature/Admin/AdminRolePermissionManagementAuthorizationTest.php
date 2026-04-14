<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRolePermissionManagementAuthorizationTest extends TestCase
{
    public function test_admin_can_assign_user_role_via_super_admin_gate_even_without_explicit_permission(): void
    {
        Permission::findOrCreate('assign roles', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions([]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        $target = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $target->assignRole('learner');

        $this->actingAs($admin)
            ->post(route('admin.rbac.users.assign-role', $target), [
                'role' => 'instructor',
                'reason' => 'Role realignment',
            ])
            ->assertRedirect(route('admin.users.show', $target));
    }

    public function test_admin_with_assign_roles_permission_can_assign_user_role(): void
    {
        Permission::findOrCreate('assign roles', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(['assign roles']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        $target = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $target->assignRole('learner');

        $this->actingAs($admin)
            ->post(route('admin.rbac.users.assign-role', $target), [
                'role' => 'instructor',
                'reason' => 'Role realignment',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertTrue($target->hasRole('instructor'));
    }

    public function test_admin_can_sync_permissions_via_super_admin_gate_even_without_explicit_permission(): void
    {
        Permission::findOrCreate('manage permissions', 'web');
        Permission::findOrCreate('create modules', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions([]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        $instructorRole = Role::findOrCreate('instructor', 'web');

        $this->actingAs($admin)
            ->post(route('admin.rbac.roles.sync-permissions', $instructorRole), [
                'permissions' => ['create modules'],
            ])
            ->assertRedirect();
    }

    public function test_admin_with_manage_permissions_can_sync_role_permissions(): void
    {
        Permission::findOrCreate('manage permissions', 'web');
        Permission::findOrCreate('create modules', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(['manage permissions']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        $instructorRole = Role::findOrCreate('instructor', 'web');
        $instructorRole->syncPermissions([]);

        $this->actingAs($admin)
            ->post(route('admin.rbac.roles.sync-permissions', $instructorRole), [
                'permissions' => ['create modules'],
            ])
            ->assertRedirect();

        $this->assertTrue($instructorRole->fresh()->hasPermissionTo('create modules'));
    }

    public function test_non_admin_cannot_access_rbac_management_endpoints(): void
    {
        $learner = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $learner->assignRole('learner');

        $target = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $target->assignRole('learner');

        $instructorRole = Role::findOrCreate('instructor', 'web');

        $this->actingAs($learner)
            ->post(route('admin.rbac.users.assign-role', $target), [
                'role' => 'instructor',
                'reason' => 'Role realignment',
            ])
            ->assertForbidden();

        $this->actingAs($learner)
            ->post(route('admin.rbac.roles.sync-permissions', $instructorRole), [
                'permissions' => ['create modules'],
            ])
            ->assertForbidden();
    }
}
