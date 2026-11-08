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
            $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();

            foreach (['review instructor identities', 'review instructor applications'] as $name) {
                $permission = Permission::findOrCreate($name, 'web');
                $admin?->givePermissionTo($permission);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain existing grants and review access on rollback.
    }
};
