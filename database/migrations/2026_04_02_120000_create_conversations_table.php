<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_two_id')->constrained('users')->cascadeOnDelete();
            $table->string('pair_key', 64);
            $table->string('conversation_type', 32);
            $table->string('status', 32)->default('active');
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes')->nullOnDelete();
            $table->string('context_key', 128)->default('direct');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['pair_key', 'context_key'], 'conversations_pair_context_unique');
            $table->index(['participant_one_id', 'participant_two_id'], 'conversations_participants_idx');
            $table->index(['last_message_at'], 'conversations_last_message_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
