<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementFeatureTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            'manage permissions',
            'manage user relationships',
        ];

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

    public function test_admin_can_create_update_and_archive_user(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $storeResponse = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Lifecycle User',
                'email' => 'lifecycle-user@example.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'learner',
                'status' => 'active',
                'birthdate' => now()->subYears(11)->format('Y-m-d'),
            ]);

        $storeResponse->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'lifecycle-user@example.test')->firstOrFail();

        $this->assertSame('learner', $createdUser->role);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $createdUser), [
                'name' => 'Lifecycle User Updated',
                'email' => 'lifecycle-user@example.test',
                'password' => '',
                'password_confirmation' => '',
                'role' => 'instructor',
                'role_change_reason' => 'Promoted after content review.',
                'status' => 'inactive',
                'birthdate' => now()->subYears(20)->format('Y-m-d'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $createdUser), [
                'status' => 'archived',
                'reason' => 'No longer active in platform operations.',
            ])
            ->assertRedirect(route('admin.users.show', $createdUser));

        $createdUser->refresh();

        $this->assertSame('instructor', $createdUser->role);
        $this->assertTrue($createdUser->hasRole('instructor'));
        $this->assertSame('archived', $createdUser->status);
        $this->assertSame('instructor', $createdUser->account_type);
    }

    public function test_admin_users_index_renders_segmented_management_controls(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Admin User Management', false)
            ->assertSee('Monitor account lifecycle', false);
    }

    public function test_admin_can_set_direct_permission_overrides_when_creating_user(): void
    {
        $admin = $this->createAdminUser();
        Permission::findOrCreate('manage users', 'web');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Override User',
                'email' => 'override-user@example.test',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'learner',
                'status' => 'active',
                'apply_permission_overrides' => '1',
                'direct_permissions' => ['manage users'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdUser = User::query()->where('email', 'override-user@example.test')->firstOrFail();

        $this->assertTrue($createdUser->hasDirectPermission('manage users'));
    }
}
