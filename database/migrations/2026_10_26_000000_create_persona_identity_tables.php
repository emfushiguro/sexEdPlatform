<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persona_inquiries')) {
            Schema::create('persona_inquiries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('learner_identity_verification_id')
                    ->nullable()->constrained()->cascadeOnDelete();
                $table->string('flow_type', 32);
                $table->unsignedInteger('target_submission_round');
                $table->unsignedInteger('attempt_number');
                $table->string('local_document_type', 32);
                $table->string('local_government_id_type', 40)->nullable();
                $table->string('template_key', 24);
                $table->string('template_id', 80);
                $table->uuid('reference_id')->unique();
                $table->uuid('idempotency_key')->unique();
                $table->string('persona_inquiry_id', 80)->nullable()->unique();
                $table->string('government_id_verification_id', 80)->nullable();
                $table->string('selfie_verification_id', 80)->nullable();
                $table->string('persona_status', 40)->nullable();
                $table->string('verification_outcome', 24)->default('not_ready');
                $table->char('country_code', 2)->nullable();
                $table->string('id_class', 32)->nullable();
                $table->string('country_policy_status', 24)->default('not_applicable');
                $table->string('document_policy_status', 24)->default('not_applicable');
                $table->boolean('is_sandbox')->default(true);
                $table->timestamp('persona_event_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamp('superseded_at')->nullable();
                $table->string('failure_category', 48)->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'attempt_number']);
                $table->index([
                    'user_id', 'target_submission_round', 'superseded_at',
                ], 'persona_current_attempt_lookup');
            });
        }

        if (! Schema::hasTable('persona_webhook_events')) {
            Schema::create('persona_webhook_events', function (Blueprint $table): void {
                $table->id();
                $table->string('event_id', 80)->unique();
                $table->string('event_type', 80);
                $table->string('persona_inquiry_id', 80)->nullable()->index();
                $table->timestamp('event_created_at');
                $table->string('processing_status', 24)->default('received');
                $table->timestamp('received_at')->useCurrent();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('failure_category', 48)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('guardian_identity_audits')) {
            Schema::create('guardian_identity_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('persona_inquiry_id')
                    ->nullable()->constrained('persona_inquiries')->nullOnDelete();
                $table->string('action', 32);
                $table->string('from_status', 24)->nullable();
                $table->string('to_status', 24)->nullable();
                $table->unsignedInteger('attempt_number');
                $table->text('reason')->nullable();
                $table->timestamp('created_at');
                $table->index(['guardian_id', 'created_at']);
            });
        }
    }

    public function down(): never
    {
        throw new \LogicException(
            'Persona identity and guardian audit history requires an explicit retention-approved migration.'
        );
    }
};
