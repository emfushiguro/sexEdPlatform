<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_moderation_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('warning_count')->default(0);
            $table->string('current_restriction_status')->nullable();
            $table->timestamp('restriction_starts_at')->nullable();
            $table->timestamp('restriction_ends_at')->nullable();
            $table->timestamp('last_violation_at')->nullable();
            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->timestamps();

            $table->index(['current_restriction_status', 'restriction_ends_at'], 'instructor_mod_profile_restriction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_moderation_profiles');
    }
};
