<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_feedback_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_feedback_id')->constrained('platform_feedback')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_role', 20);
            $table->text('body');
            $table->timestamps();
            $table->index(['platform_feedback_id', 'created_at'], 'pfm_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feedback_messages');
    }
};
