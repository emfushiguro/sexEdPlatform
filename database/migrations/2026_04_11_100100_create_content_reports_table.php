<?php

use App\Enums\ContentReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 32);
            $table->unsignedBigInteger('target_id');
            $table->string('reason_code', 64);
            $table->string('status', 32)->default(ContentReportStatus::Submitted->value);
            $table->longText('details_html')->nullable();
            $table->text('latest_outcome_message')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'content_reports_target_idx');
            $table->index(['reporter_id', 'target_type', 'target_id', 'status'], 'content_reports_dedup_idx');
            $table->index(['status', 'created_at'], 'content_reports_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};
