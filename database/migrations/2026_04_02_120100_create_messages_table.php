<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message_body');
            $table->timestamp('read_at')->nullable();
            $table->string('message_type', 32)->default('text');
            $table->string('attachment_url')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id'], 'messages_conversation_id_id_idx');
            $table->index(['sender_id'], 'messages_sender_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
