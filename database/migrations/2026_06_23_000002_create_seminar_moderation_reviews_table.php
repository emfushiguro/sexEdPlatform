<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_moderation_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->dateTime('reviewed_at');
            $table->timestamps();

            $table->index(['seminar_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_moderation_reviews');
    }
};
