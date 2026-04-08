<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUsersAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_non_admin_user_cannot_access_admin_users_routes(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        Role::findOrCreate('learner', 'web');
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_without_create_permission_cannot_create_user(): void
    {
        Permission::findOrCreate('view users', 'web');
        Permission::findOrCreate('create users', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(['view users']);

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Blocked Admin Create',
                'email' => 'blocked-create@example.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'learner',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_admin_without_relationship_permission_cannot_attach_relationship(): void
    {
        Permission::findOrCreate('view users', 'web');
        Permission::findOrCreate('manage user relationships', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(['view users']);

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $parent = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $child = User::factory()->create(['role' => 'learner', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.relationships.attach'), [
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
            ])
            ->assertForbidden();
    }
}
