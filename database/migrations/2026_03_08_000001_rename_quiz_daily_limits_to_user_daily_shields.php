<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create user_daily_shields from scratch, migrate data, drop old table.
     * This avoids the FK/index ordering issues in MySQL when restructuring.
     */
    public function up(): void
    {
        // Already done if a previous run completed
        if (Schema::hasTable('user_daily_shields') && !Schema::hasTable('quiz_daily_limits')) {
            return;
        }

        // 1. Create the new table with correct structure
        if (!Schema::hasTable('user_daily_shields')) {
            Schema::create('user_daily_shields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->integer('shields_remaining')->default(3);
                $table->date('date');
                $table->timestamps();
                $table->unique(['user_id', 'date']);
            });
        }

        // 2. Migrate existing data (map attempts → shields_remaining, per user+date, ignoring quiz_id)
        if (Schema::hasTable('quiz_daily_limits')) {
            DB::statement('
                INSERT IGNORE INTO user_daily_shields (user_id, shields_remaining, date, created_at, updated_at)
                SELECT user_id,
                       GREATEST(0, 3 - SUM(attempts)) AS shields_remaining,
                       date,
                       MIN(created_at),
                       MAX(updated_at)
                FROM quiz_daily_limits
                GROUP BY user_id, date
            ');

            // 3. Drop the old table
            Schema::dropIfExists('quiz_daily_limits');
        }
    }

    public function down(): void
    {
        // Recreate quiz_daily_limits
        if (!Schema::hasTable('quiz_daily_limits')) {
            Schema::create('quiz_daily_limits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
                $table->integer('attempts')->default(0);
                $table->date('date');
                $table->timestamps();
                $table->unique(['user_id', 'quiz_id', 'date']);
            });
        }

        Schema::dropIfExists('user_daily_shields');
    }

};
