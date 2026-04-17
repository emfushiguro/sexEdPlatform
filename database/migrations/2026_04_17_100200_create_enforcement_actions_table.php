<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enforcement_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('moderation_case_id')->nullable()->constrained('moderation_cases')->nullOnDelete();
            $table->string('action_type', 64);
            $table->string('severity_level', 16);
            $table->string('trigger_type', 16)->default('manual');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 24)->default('executed');
            $table->foreignId('issued_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('skip_ladder')->default(false);
            $table->text('skip_rationale')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'enforcement_actions_user_status_idx');
            $table->index(['action_type', 'trigger_type'], 'enforcement_actions_type_trigger_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enforcement_actions');
    }
};
