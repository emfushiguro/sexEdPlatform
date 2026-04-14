<?php

namespace Tests\Feature\Rbac;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacSeederIdempotencyTest extends TestCase
{
    public function test_running_rbac_seeders_twice_keeps_counts_and_assignments_stable(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $permissionCountBefore = Permission::query()->count();
        $roleCountBefore = Role::query()->count();
        $rolePermissionPivotBefore = DB::table('role_has_permissions')->count();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame($permissionCountBefore, Permission::query()->count());
        $this->assertSame($roleCountBefore, Role::query()->count());
        $this->assertSame($rolePermissionPivotBefore, DB::table('role_has_permissions')->count());

        $admin = Role::findByName('admin', 'web');
        $instructor = Role::findByName('instructor', 'web');

        $this->assertTrue($admin->hasPermissionTo('manage users'));
        $this->assertFalse($instructor->hasPermissionTo('publish modules'));
    }
}
