<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_feedback', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 30)->unique();
            $table->uuid('submission_token')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_role', 40);
            $table->string('type', 40)->index();
            $table->string('subject', 180);
            $table->longText('description');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('affected_path', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('may_contact')->default(false);
            $table->string('attachment_path')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->text('internal_note')->nullable();
            $table->text('staff_response')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('testimonial_consent')->default(false);
            $table->string('testimonial_display_name', 100)->nullable();
            $table->boolean('testimonial_show_role')->default(false);
            $table->boolean('testimonial_show_profile_image')->default(false);
            $table->timestamp('testimonial_consented_at')->nullable();
            $table->timestamp('testimonial_consent_withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feedback');
    }
};
