<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspension_appeals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_suspension_id')->constrained('user_suspensions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending_review');
            $table->text('appeal_reason');
            $table->json('evidence_payload')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_decision_notes')->nullable();
            $table->timestamp('clarification_requested_at')->nullable();
            $table->timestamps();

            $table->index(['user_suspension_id', 'status'], 'suspension_appeals_suspension_status_idx');
            $table->index(['user_id', 'submitted_at'], 'suspension_appeals_user_submitted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspension_appeals');
    }
};
