<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('moderation_case_id')->constrained('moderation_cases')->cascadeOnDelete();
            $table->string('violation_type', 64);
            $table->string('severity_level', 16);
            $table->unsignedInteger('violation_points')->default(0);
            $table->string('trigger_source', 32)->default('manual');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('issued_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'violations_user_created_idx');
            $table->index(['user_id', 'expires_at'], 'violations_user_expires_idx');
            $table->index(['violation_type', 'severity_level'], 'violations_type_severity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
