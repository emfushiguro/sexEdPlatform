<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('initial_message');
            $table->foreignId('accepted_conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['instructor_id', 'status'], 'message_requests_instructor_status_idx');
            $table->index(['requester_id', 'status'], 'message_requests_requester_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_requests');
    }
};
