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
        Schema::table('lessons', function (Blueprint $table) {
            // Remove old content-related columns (content now in lesson_topics)
            if (Schema::hasColumn('lessons', 'content')) {
                $table->dropColumn('content');
            }
            if (Schema::hasColumn('lessons', 'video_url')) {
                $table->dropColumn('video_url');
            }
            
            // Add columns that should be in lessons
            if (!Schema::hasColumn('lessons', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('lessons', 'duration')) {
                $table->integer('duration')->default(0)->after('description')->comment('Auto-calculated from topics in minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();
            $table->dropColumn(['description', 'duration']);
        });
    }
};
