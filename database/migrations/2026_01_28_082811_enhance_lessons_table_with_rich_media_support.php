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
            // Add video file path for uploaded videos (alternative to streaming URLs)
            $table->string('video_file_path')->nullable()->after('video_id');
            
            // Add description/instructions field for all lesson types
            $table->text('description')->nullable()->after('text_content');
            
            // Add image attachments support (JSON array of image paths)
            $table->json('image_attachments')->nullable()->after('description');
            
            // Add slideshow data (JSON array for slides with images/text)
            $table->json('slideshow_data')->nullable()->after('image_attachments');
            
            // Add interactive activity configuration (JSON for activity type and settings)
            $table->json('interactive_config')->nullable()->after('slideshow_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'video_file_path',
                'description',
                'image_attachments',
                'slideshow_data',
                'interactive_config',
            ]);
        });
    }
};
