<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('roles')
            ->where('name', 'learner')
            ->where('guard_name', 'web')
            ->exists();

        if (! $exists) {
            DB::table('roles')->insert([
                'name' => 'learner',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'learner')
            ->where('guard_name', 'web')
            ->delete();
    }
};
