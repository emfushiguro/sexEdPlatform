<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_guides', function (Blueprint $table): void {
            $table->id();
            $table->string('guide_key', 100);
            $table->string('target_role', 50);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['guide_key', 'target_role', 'status'], 'og_lookup_index');
        });

        Schema::create('onboarding_guide_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('onboarding_guide_id');
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->text('description');
            $table->string('video_disk', 50)->default('local');
            $table->string('video_path');
            $table->string('video_original_name');
            $table->string('video_mime_type', 100);
            $table->unsignedBigInteger('video_size');
            $table->boolean('requires_recompletion')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('onboarding_guide_id', 'ogv_guide_fk')
                ->references('id')->on('onboarding_guides')->restrictOnDelete();
            $table->unique(
                ['onboarding_guide_id', 'version_number'],
                'ogv_guide_number_unique'
            );
            $table->index(
                ['onboarding_guide_id', 'published_at'],
                'ogv_publication_index'
            );
        });

        Schema::table('onboarding_guides', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'og_current_version_fk')
                ->references('id')->on('onboarding_guide_versions')->restrictOnDelete();
        });

        Schema::create('user_onboarding_guide_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('onboarding_guide_version_id');
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id', 'uogs_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('onboarding_guide_version_id', 'uogs_version_fk')
                ->references('id')->on('onboarding_guide_versions')->restrictOnDelete();
            $table->unique(
                ['user_id', 'onboarding_guide_version_id'],
                'uogs_user_version_unique'
            );
        });
    }

    public function down(): void
    {
        // Forward-only: retain guide versions and user history.
    }
};
