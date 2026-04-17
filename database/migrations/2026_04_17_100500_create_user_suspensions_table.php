<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_suspensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enforcement_action_id')->nullable()->constrained('enforcement_actions')->nullOnDelete();
            $table->foreignId('moderation_case_id')->nullable()->constrained('moderation_cases')->nullOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revoked_reason')->nullable();
            $table->string('appeal_status', 24)->default('none');
            $table->timestamp('appeal_submitted_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'user_suspensions_user_status_idx');
            $table->index(['status', 'ends_at'], 'user_suspensions_status_ends_idx');
            $table->index(['appeal_status', 'created_at'], 'user_suspensions_appeal_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_suspensions');
    }
};
