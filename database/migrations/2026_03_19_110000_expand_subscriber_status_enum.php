<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscribers MODIFY status ENUM('active','scheduled_cancel','grace_period','inactive','pending','past_due','cancelled','expired') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscribers MODIFY status ENUM('active','inactive','pending','past_due','cancelled','expired') NOT NULL DEFAULT 'active'");
    }
};
