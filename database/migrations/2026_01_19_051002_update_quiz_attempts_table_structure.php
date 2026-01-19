<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['user_answer', 'is_correct']);
            
            // Add new columns
            $table->json('answers')->nullable()->after('quiz_id');
            $table->boolean('passed')->default(false)->after('score');
            $table->timestamp('started_at')->nullable()->after('passed');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['answers', 'passed', 'started_at', 'completed_at']);
            
            // Restore old columns
            $table->string('user_answer');
            $table->boolean('is_correct');
        });
    }
};
