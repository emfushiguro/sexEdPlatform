<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('message_reactions');
    }

    public function down(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'reaction'], 'message_reactions_unique_idx');
            $table->index(['message_id', 'reaction'], 'message_reactions_message_reaction_idx');
        });
    }
};
