<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_identity_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreign('user_id', 'idv_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->string('source', 32);
            $table->foreignId('learner_identity_verification_id')->nullable();
            $table->foreign('learner_identity_verification_id', 'idv_learner_fk')
                ->references('id')->on('learner_identity_verifications')->nullOnDelete();
            $table->string('status', 32)->default('awaiting_verification');
            $table->unsignedInteger('submission_round')->default(1);
            $table->longText('declared_snapshot')->nullable();
            $table->longText('comparison_summary')->nullable();
            $table->boolean('sandbox_manual_review')->default(false);
            $table->boolean('resubmission_allowed')->default(true);
            $table->foreignId('reviewed_by')->nullable();
            $table->foreign('reviewed_by', 'idv_reviewer_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('action_required_at')->nullable();
            $table->string('reason_code', 64)->nullable();
            $table->text('reason_note')->nullable();
            $table->json('requested_action_codes')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'submission_round'], 'instructor_idv_user_round_uq');
            $table->index(
                ['user_id', 'superseded_at', 'submission_round'],
                'instructor_idv_current_lookup_idx',
            );
            $table->index(
                ['status', 'superseded_at', 'submitted_at'],
                'instructor_idv_review_queue_idx',
            );
        });

        Schema::create('instructor_identity_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instructor_identity_verification_id');
            $table->foreign('instructor_identity_verification_id', 'id_audit_case_fk')
                ->references('id')->on('instructor_identity_verifications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable();
            $table->foreign('actor_id', 'id_audit_actor_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreignId('persona_inquiry_id')->nullable();
            $table->foreign('persona_inquiry_id', 'id_audit_persona_fk')
                ->references('id')->on('persona_inquiries')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->unsignedInteger('submission_round');
            $table->text('reason')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(
                ['instructor_identity_verification_id', 'created_at'],
                'instructor_idaudit_case_created_idx',
            );
        });
    }

    public function down(): never
    {
        throw new LogicException(
            'Instructor identity verification and audit history must not be removed by rollback.'
        );
    }
};
