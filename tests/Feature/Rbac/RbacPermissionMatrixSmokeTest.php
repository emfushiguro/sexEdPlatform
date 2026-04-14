<?php

namespace Tests\Feature\Rbac;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacPermissionMatrixSmokeTest extends TestCase
{
    public function test_canonical_permission_catalog_is_seeded(): void
    {
        $expected = [
            'access admin panel',
            'assign roles',
            'review modules',
            'access chat',
            'view learner progress',
        ];

        foreach ($expected as $permissionName) {
            $this->assertTrue(
                Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->exists(),
                sprintf('Expected permission "%s" to be seeded on web guard.', $permissionName)
            );
        }
    }

    public function test_parent_role_exists_for_monitoring_capabilities(): void
    {
        $this->assertTrue(
            Role::query()->where('name', 'parent')->where('guard_name', 'web')->exists(),
            'Expected parent role to be seeded for monitoring scope.'
        );
    }
}
