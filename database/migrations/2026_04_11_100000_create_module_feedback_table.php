<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->longText('review_html');
            $table->longText('instructor_reply_html')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'learner_id'], 'module_feedback_unique_reviewer');
            $table->index(['module_id', 'rating'], 'module_feedback_module_rating_idx');
            $table->index(['learner_id', 'created_at'], 'module_feedback_learner_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_feedback');
    }
};
