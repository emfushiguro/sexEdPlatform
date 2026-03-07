<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected','pending_parent_approval') NOT NULL DEFAULT 'approved'");
    }

    public function down(): void
    {
        // Update any pending_parent_approval rows to rejected before reverting
        DB::table('module_enrollments')
            ->where('status', 'pending_parent_approval')
            ->update(['status' => 'rejected']);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved'");
        }
    }
};
