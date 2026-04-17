<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_automation_rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->constrained('moderation_automation_rules')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('conditions');
            $table->string('action_type', 64);
            $table->string('severity_level', 16)->nullable();
            $table->string('trigger_type', 24)->default('automatic');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['rule_id', 'version_number'], 'mod_auto_rule_versions_rule_version_uq');
            $table->index(['rule_id', 'is_active'], 'mod_auto_rule_versions_rule_active_idx');
        });

        Schema::table('moderation_automation_rules', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'mod_auto_rules_current_version_fk')
                ->references('id')
                ->on('moderation_automation_rule_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('moderation_automation_rules', function (Blueprint $table): void {
            $table->dropForeign('mod_auto_rules_current_version_fk');
        });

        Schema::dropIfExists('moderation_automation_rule_versions');
    }
};
