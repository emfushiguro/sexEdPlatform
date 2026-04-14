<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Services\Admin\UserManagementService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacLegacyRoleSyncTest extends TestCase
{
    public function test_change_role_syncs_spatie_assignment_and_legacy_column(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('learner', 'web');

        $adminActor = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $adminActor->assignRole('admin');

        $target = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $target->assignRole('learner');

        app(UserManagementService::class)->changeRole(
            user: $target,
            newRole: 'admin',
            reason: 'Promotion for governance ownership',
            actorId: (int) $adminActor->id,
            request: null,
        );

        $target->refresh();

        $this->assertTrue($target->hasRole('admin'));
        $this->assertSame('admin', $target->role);
    }
}
