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
            // Add content type field
            $table->string('content_type')->default('text')->after('title');
            
            // Add text_content field (keep old 'content' for backward compatibility)
            $table->text('text_content')->nullable()->after('content');
            
            // Add description/instructions field
            $table->text('description')->nullable()->after('text_content');
            
            // Add video fields
            $table->string('video_provider')->nullable()->after('description');
            $table->string('video_id')->nullable()->after('video_provider');
            $table->string('video_file_path')->nullable()->after('video_id');
            
            // Add file path for worksheets
            $table->string('file_path')->nullable()->after('video_file_path');
            
            // Add JSON fields for attachments and interactive content
            $table->json('image_attachments')->nullable()->after('file_path');
            $table->json('slideshow_data')->nullable()->after('image_attachments');
            $table->json('interactive_config')->nullable()->after('slideshow_data');
            
            // Add duration field
            $table->integer('duration')->default(10)->after('interactive_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'content_type',
                'text_content',
                'description',
                'video_provider',
                'video_id',
                'video_file_path',
                'file_path',
                'image_attachments',
                'slideshow_data',
                'interactive_config',
                'duration'
            ]);
        });
    }
};
