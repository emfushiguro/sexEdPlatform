<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rule_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('moderation_automation_rules')->nullOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('matched_violation_ids')->nullable();
            $table->json('condition_snapshot')->nullable();
            $table->string('action_executed', 64)->nullable();
            $table->foreignId('enforcement_action_id')->nullable()->constrained('enforcement_actions')->nullOnDelete();
            $table->string('status', 24);
            $table->string('idempotency_key', 120)->unique();
            $table->timestamp('executed_at');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'executed_at'], 'auto_rule_logs_target_executed_idx');
            $table->index(['rule_id', 'status'], 'auto_rule_logs_rule_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rule_logs');
    }
};
