<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscribers MODIFY plan VARCHAR(100) NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        DB::statement("UPDATE subscribers SET plan = 'premium' WHERE plan NOT IN ('free','premium')");
        DB::statement("ALTER TABLE subscribers MODIFY plan ENUM('free','premium') NOT NULL DEFAULT 'free'");
    }
};
