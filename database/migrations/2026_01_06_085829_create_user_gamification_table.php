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
        Schema::create('user_gamification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('level')->default(1);
            $table->integer('score')->default(0);
            $table->integer('streak_count')->default(0);
            $table->timestamp('last_act_at')->nullable(); // last activity timestamp for streak tracking
            $table->timestamps();

            $table->unique('user_id');
            $table->index('score'); // for leaderboards
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_gamification');
    }
};
