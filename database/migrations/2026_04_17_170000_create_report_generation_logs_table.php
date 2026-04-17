<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('generated_at');
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('generated_by_role', 50)->nullable();
            $table->string('report_scope', 50);
            $table->string('export_format', 20);
            $table->json('filters_json');
            $table->string('checksum_hash', 64);
            $table->unsignedInteger('row_count')->nullable();
            $table->json('summary_snapshot_json')->nullable();
            $table->timestamps();

            $table->index(['generated_at'], 'idx_report_generation_logs_generated_at');
            $table->index(['generated_by_user_id', 'generated_at'], 'idx_report_generation_logs_user_generated');
            $table->index(['report_scope', 'export_format'], 'idx_report_generation_logs_scope_format');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_generation_logs');
    }
};
