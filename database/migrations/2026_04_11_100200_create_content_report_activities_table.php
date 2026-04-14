<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_report_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_report_id')->constrained('content_reports')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('activity_type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->string('action_code', 64)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['content_report_id', 'created_at'], 'content_report_activities_report_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_report_activities');
    }
};
