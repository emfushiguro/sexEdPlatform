<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'reporter_id'], 'message_reports_unique_reporter_idx');
            $table->index(['conversation_id', 'status'], 'message_reports_conversation_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reports');
    }
};
