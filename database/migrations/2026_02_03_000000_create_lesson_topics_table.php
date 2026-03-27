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
        Schema::create('lesson_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['video', 'text', 'worksheet', 'quiz', 'interactive'])->default('text');
            
            // Video fields
            $table->string('video_provider')->nullable(); // youtube, vimeo, local
            $table->string('video_id')->nullable();
            $table->string('video_file_path')->nullable();
            
            // Text content
            $table->longText('text_content')->nullable();
            
            // Worksheet/File
            $table->string('file_path')->nullable();
            
            // Quiz reference
            $table->foreignId('quiz_id')->nullable()->constrained()->onDelete('set null');
            
            // Interactive activity
            $table->json('interactive_config')->nullable();
            
            // Image attachments for text
            $table->json('image_attachments')->nullable();
            $table->json('slideshow_data')->nullable();
            
            $table->integer('duration')->nullable()->comment('Duration in minutes');
            $table->boolean('is_prerequisite')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Topic progress tracking
        Schema::create('lesson_topic_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_topic_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'lesson_topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_topic_progress');
        Schema::dropIfExists('lesson_topics');
    }
};
