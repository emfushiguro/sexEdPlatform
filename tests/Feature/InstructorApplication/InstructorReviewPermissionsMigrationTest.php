<?php

namespace Tests\Feature\InstructorApplication;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorReviewPermissionsMigrationTest extends TestCase
{
    public function test_migration_adds_review_permissions_to_admin_without_changing_other_access(): void
    {
        $names = ['review instructor identities', 'review instructor applications'];
        $admin = Role::findByName('admin', 'web');
        $learner = Role::findByName('learner', 'web');
        $adminPermissions = $admin->permissions()->whereNotIn('name', $names)->pluck('name')->sort()->values()->all();
        $learnerPermissions = $learner->permissions()->pluck('name')->sort()->values()->all();

        Permission::whereIn('name', $names)->where('guard_name', 'web')->get()->each->delete();

        $migration = require database_path('migrations/2026_11_02_000001_add_instructor_review_permissions.php');
        $migration->up();
        $migration->up();

        foreach ($names as $name) {
            $this->assertSame(1, Permission::where('name', $name)->where('guard_name', 'web')->count());
            $this->assertTrue($admin->fresh()->hasPermissionTo($name));
            $this->assertFalse($learner->fresh()->hasPermissionTo($name));
        }

        $this->assertSame($adminPermissions, $admin->permissions()->whereNotIn('name', $names)->pluck('name')->sort()->values()->all());
        $this->assertSame($learnerPermissions, $learner->permissions()->pluck('name')->sort()->values()->all());
    }
}
