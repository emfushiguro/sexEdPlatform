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
        $this->assertTrue($target->hasRole('instructor'));

        $transition = RoleTransition::query()->where('user_id', $target->id)->latest('id')->first();

        $this->assertNotNull($transition);
        $this->assertSame('learner', $transition->from_role);
        $this->assertSame('instructor', $transition->to_role);
        $this->assertSame('Approved for instructor onboarding.', $transition->reason);
    }

    public function test_role_change_accepts_optional_reason_and_saves_custom_notes(): void
    {
        $admin = $this->createAdminUser();

        $target = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $target), [
                'role' => 'instructor',
                'custom_notes' => '<p>Promoted after portfolio review.</p>',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $transition = RoleTransition::query()->where('user_id', $target->id)->latest('id')->first();

        $this->assertNotNull($transition);
        $this->assertNull($transition->reason);
        $this->assertSame('<p>Promoted after portfolio review.</p>', $transition->custom_notes);
    }

    public function test_user_profile_role_change_form_supports_custom_notes_editor(): void
    {
        $this->withoutVite();

        $admin = $this->createAdminUser();
        $target = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('name="custom_notes"', false)
            ->assertSee('js-role-change-editor', false)
            ->assertSee('build/tinymce/tinymce.min.js', false);
    }
}
