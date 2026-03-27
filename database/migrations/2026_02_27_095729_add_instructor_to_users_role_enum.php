<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'instructor' to the role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('learner', 'organization', 'clinic', 'counselor', 'admin', 'instructor') NOT NULL DEFAULT 'learner'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'instructor' from the role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('learner', 'organization', 'clinic', 'counselor', 'admin') NOT NULL DEFAULT 'learner'");
    }
};
