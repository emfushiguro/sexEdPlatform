<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appeal_thread_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('suspension_appeal_id')->constrained('suspension_appeals')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role', 24);
            $table->text('message_body');
            $table->foreignId('parent_message_id')->nullable()->constrained('appeal_thread_messages')->nullOnDelete();
            $table->timestamps();

            $table->index(['suspension_appeal_id', 'created_at'], 'appeal_thread_messages_appeal_created_idx');
            $table->index(['sender_user_id', 'created_at'], 'appeal_thread_messages_sender_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appeal_thread_messages');
    }
};
