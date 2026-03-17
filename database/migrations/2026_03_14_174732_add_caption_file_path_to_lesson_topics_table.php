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
        Schema::table('lesson_topics', function (Blueprint $table) {
            $table->string('caption_file_path')->nullable()->after('video_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_topics', function (Blueprint $table) {
            $table->dropColumn('caption_file_path');
        });
    }
};
