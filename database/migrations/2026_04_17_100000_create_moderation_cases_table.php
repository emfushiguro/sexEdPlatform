<?php

use App\Enums\ModerationCaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_reference_code', 32)->unique();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('content_type', 64);
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('case_source', 64);
            $table->string('status', 32)->default(ModerationCaseStatus::Reported->value);
            $table->string('severity_level', 16)->nullable();
            $table->string('decision', 64)->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['case_source', 'status'], 'moderation_cases_source_status_idx');
            $table->index(['reported_user_id', 'created_at'], 'moderation_cases_user_created_idx');
            $table->index(['content_type', 'content_id'], 'moderation_cases_content_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_cases');
    }
};
