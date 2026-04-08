<?php

namespace Tests\Feature\Admin;

use App\Models\RoleTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserRoleTransitionAuditTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $permissions = ['view users', 'manage roles', 'edit users'];

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

    public function test_role_change_writes_transition_audit_record(): void
    {
        $admin = $this->createAdminUser();

        $target = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $target), [
                'role' => 'instructor',
                'reason' => 'Approved for instructor onboarding.',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();

        $this->assertSame('instructor', $target->role);

        $transition = RoleTransition::query()->where('user_id', $target->id)->latest('id')->first();

        $this->assertNotNull($transition);
        $this->assertSame('learner', $transition->from_role);
        $this->assertSame('instructor', $transition->to_role);
        $this->assertSame('Approved for instructor onboarding.', $transition->reason);
    }
}
