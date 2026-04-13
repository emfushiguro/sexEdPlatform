<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type', 40)->nullable()->after('status');
            $table->string('age_bracket_cached', 20)->nullable()->after('birthdate');

            $table->index(['role', 'status'], 'users_role_status_idx');
            $table->index(['account_type', 'status'], 'users_account_type_status_idx');
            $table->index(['age_bracket_cached', 'status'], 'users_age_bracket_status_idx');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'archived') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')
                ->where('status', 'archived')
                ->update(['status' => 'inactive']);

            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_status_idx');
            $table->dropIndex('users_account_type_status_idx');
            $table->dropIndex('users_age_bracket_status_idx');
            $table->dropColumn(['account_type', 'age_bracket_cached']);
        });
    }
};
