<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_violation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('module_review_request_id')->constrained('module_review_requests')->cascadeOnDelete();
            $table->string('reason_code');
            $table->text('guidance_note');
            $table->unsignedInteger('violation_sequence');
            $table->string('suggested_penalty_action')->nullable();
            $table->string('confirmed_penalty_action')->nullable();
            $table->foreignId('confirmed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'instructor_violation_user_created_idx');
            $table->index(['module_review_request_id'], 'instructor_violation_review_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_violation_histories');
    }
};
