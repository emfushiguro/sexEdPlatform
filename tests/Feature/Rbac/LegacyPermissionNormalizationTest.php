<?php

namespace Tests\Feature\Rbac;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyPermissionNormalizationTest extends TestCase
{
    public function test_legacy_module_permission_assignments_are_moved_to_canonical_names(): void
    {
        $role = Role::findByName('instructor', 'web');
        $legacy = Permission::create([
            'name' => 'edit-modules',
            'guard_name' => 'web',
        ]);

        DB::table('role_has_permissions')->insert([
            'permission_id' => $legacy->id,
            'role_id' => $role->id,
        ]);

        $migration = require database_path(
            'migrations/2026_10_13_000001_normalize_legacy_rbac_permission_names.php',
        );
        $migration->up();

        $this->assertDatabaseMissing('permissions', [
            'name' => 'edit-modules',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'edit modules',
            'guard_name' => 'web',
        ]);
        $this->assertTrue($role->fresh()->hasPermissionTo('edit modules'));
    }
}
