<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learner_identity_verifications')) {
            Schema::create('learner_identity_verifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('pathway', 16);
                $table->string('document_type', 32)->nullable();
                $table->string('government_id_type', 40)->nullable();
                $table->string('government_id_type_other', 80)->nullable();
                $table->string('status', 16)->nullable();
                $table->unsignedInteger('submission_round')->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('superseded_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'pathway']);
            });
        }

        if (! Schema::hasIndex('learner_identity_verifications', 'learner_idv_path_status_superseded_idx')) {
            Schema::table('learner_identity_verifications', function (Blueprint $table): void {
                $table->index(['pathway', 'status', 'superseded_at'], 'learner_idv_path_status_superseded_idx');
            });
        }

        if (! Schema::hasTable('learner_identity_evidence')) {
            Schema::create('learner_identity_evidence', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
                $table->string('slot', 24);
                $table->string('storage_path');
                $table->string('mime_type', 64);
                $table->unsignedBigInteger('byte_size');
                $table->unsignedInteger('width');
                $table->unsignedInteger('height');
                $table->timestamp('submitted_at');
                $table->timestamps();
                $table->unique(['verification_id', 'slot']);
            });
        }

        if (! Schema::hasTable('learner_identity_audits')) {
            Schema::create('learner_identity_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 32);
                $table->string('from_status', 16)->nullable();
                $table->string('to_status', 16)->nullable();
                $table->unsignedInteger('submission_round');
                $table->text('reason')->nullable();
                $table->timestamp('created_at');
            });
        }
    }

    public function down(): never
    {
        throw new \LogicException('Learner identity verification records must not be removed by rollback.');
    }
};
