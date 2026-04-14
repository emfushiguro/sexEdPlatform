<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Transitional bridge: keep historical seeder reference while delegating
        // actual RBAC state to dedicated seeders.
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $this->command?->info('Roles and permissions seeded using canonical RBAC seeders.');
    }
}
