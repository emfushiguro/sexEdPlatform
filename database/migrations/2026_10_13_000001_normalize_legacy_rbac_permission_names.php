<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const LEGACY_TO_CANONICAL = [
        'view-modules' => 'view modules',
        'create-modules' => 'create modules',
        'edit-modules' => 'edit modules',
        'delete-modules' => 'delete modules',
        'view-lessons' => 'view lessons',
        'create-lessons' => 'create lessons',
        'edit-lessons' => 'edit lessons',
        'delete-lessons' => 'delete lessons',
        'view-quizzes' => 'view quizzes',
        'create-quizzes' => 'create quizzes',
        'edit-quizzes' => 'edit quizzes',
        'delete-quizzes' => 'delete quizzes',
        'create-topics' => 'create lesson topics',
        'edit-topics' => 'edit lesson topics',
        'delete-topics' => 'delete lesson topics',
        'view-learners' => 'view learners',
        'view-learner-progress' => 'view learner progress',
    ];

    public function up(): void
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $permissionKey = $columns['permission_pivot_key'] ?? 'permission_id';
        $roleKey = $columns['role_pivot_key'] ?? 'role_id';
        $modelKey = $columns['model_morph_key'] ?? 'model_id';

        DB::transaction(function () use ($tables, $permissionKey, $roleKey, $modelKey): void {
            foreach (self::LEGACY_TO_CANONICAL as $legacyName => $canonicalName) {
                $legacy = DB::table($tables['permissions'])
                    ->where('name', $legacyName)
                    ->where('guard_name', 'web')
                    ->first();

                if (! $legacy) {
                    continue;
                }

                $canonical = DB::table($tables['permissions'])
                    ->where('name', $canonicalName)
                    ->where('guard_name', 'web')
                    ->first();

                $canonicalId = $canonical?->id ?? DB::table($tables['permissions'])->insertGetId([
                    'name' => $canonicalName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table($tables['role_has_permissions'])
                    ->where($permissionKey, $legacy->id)
                    ->get([$roleKey])
                    ->each(function (object $pivot) use ($tables, $permissionKey, $roleKey, $canonicalId): void {
                        DB::table($tables['role_has_permissions'])->insertOrIgnore([
                            $permissionKey => $canonicalId,
                            $roleKey => $pivot->{$roleKey},
                        ]);
                    });

                DB::table($tables['model_has_permissions'])
                    ->where($permissionKey, $legacy->id)
                    ->get([$modelKey, 'model_type'])
                    ->each(function (object $pivot) use ($tables, $permissionKey, $modelKey, $canonicalId): void {
                        DB::table($tables['model_has_permissions'])->insertOrIgnore([
                            $permissionKey => $canonicalId,
                            $modelKey => $pivot->{$modelKey},
                            'model_type' => $pivot->model_type,
                        ]);
                    });

                DB::table($tables['role_has_permissions'])->where($permissionKey, $legacy->id)->delete();
                DB::table($tables['model_has_permissions'])->where($permissionKey, $legacy->id)->delete();
                DB::table($tables['permissions'])->where('id', $legacy->id)->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission names are application contracts; reverting them would restore the broken authorization state.
    }
};
