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
            // Add content type enum for better structure
            $table->enum('content_type', ['text', 'video', 'worksheet', 'interactive'])->default('text')->after('title');
            
            // Rename and optimize fields
            $table->text('text_content')->nullable()->after('content_type');
            $table->string('video_provider')->nullable()->after('text_content'); // youtube, vimeo, custom
            $table->string('video_id')->nullable()->after('video_provider'); // Video ID from provider
            $table->string('file_path')->nullable()->after('video_id'); // For worksheets/downloads
            
            // Remove old redundant fields
            $table->dropColumn(['content', 'video_url', 'type', 'description']);
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
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            
            $table->dropColumn([
                'content_type',
                'text_content',
                'video_provider',
                'video_id',
                'file_path'
            ]);
        });
    }
};
