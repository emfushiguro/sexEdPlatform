<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacLegacyRoleSyncSmokeTest extends TestCase
{
    public function test_role_sync_service_keeps_spatie_role_and_legacy_column_in_sync(): void
    {
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        app(RoleSyncService::class)->assignPrimaryRole($user, 'admin');
        $user->refresh();

        $this->assertTrue($user->hasRole('admin'));
        $this->assertSame('admin', $user->role);
    }
}
