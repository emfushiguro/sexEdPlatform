<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_feedback_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_feedback_id')->constrained('platform_feedback')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('internal_note')->nullable();
            $table->text('staff_response')->nullable();
            $table->timestamps();
            $table->index(['platform_feedback_id', 'created_at'], 'pfh_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feedback_histories');
    }
};
