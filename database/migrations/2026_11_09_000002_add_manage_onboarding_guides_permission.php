<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $permission = Permission::findOrCreate('manage onboarding guides', 'web');
            Role::query()
                ->where('name', 'admin')
                ->where('guard_name', 'web')
                ->first()
                ?->givePermissionTo($permission);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain the permission and grant on rollback.
    }
};
