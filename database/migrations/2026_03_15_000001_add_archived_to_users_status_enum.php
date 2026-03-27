<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended','archived') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Revert: remove archived users first to avoid data truncation, then shrink enum
        DB::statement("UPDATE users SET status = 'inactive' WHERE status = 'archived'");
        DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'");
    }
};
