<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->json('conditions');
            $table->string('action_type', 64);
            $table->string('severity_level', 16)->nullable();
            $table->string('trigger_type', 24)->default('automatic');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority'], 'mod_auto_rules_active_priority_idx');
            $table->index(['action_type', 'severity_level'], 'mod_auto_rules_action_severity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_automation_rules');
    }
};
