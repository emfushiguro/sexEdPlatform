<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacRouteProtectionSmokeTest extends TestCase
{
    public function test_admin_can_access_instructor_content_tools_via_permission(): void
    {
        Permission::findOrCreate('create modules', 'web');

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo('create modules');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('instructor.modules.index'))
            ->assertOk();
    }

    public function test_parent_chat_access_is_permission_driven(): void
    {
        Permission::findOrCreate('access chat', 'web');

        $parentRole = Role::findOrCreate('parent', 'web');
        $parentRole->givePermissionTo('access chat');

        $parent = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $parent->assignRole('parent');

        $this->actingAs($parent)
            ->get(route('chat.page'))
            ->assertOk();
    }
}
