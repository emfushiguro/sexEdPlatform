<?php

use App\Models\Connector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connectors', function (Blueprint $table): void {
            $table->string('organization_type')->nullable()->index();
            $table->string('logo_path')->nullable();
            $table->string('verification_status')->nullable()->index();
            $table->unsignedInteger('current_submission_round')->default(0);
            $table->timestamp('verification_submitted_at')->nullable();
        });

        DB::statement('CREATE INDEX connectors_identity_status_index ON connectors (name(150), city_code(50), barangay_code(50), status(30))');

        Schema::create('connector_verification_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')
                ->constrained('connectors', 'id', 'cvr_connector_fk')
                ->restrictOnDelete();
            $table->string('requirement_key');
            $table->string('evidence_category');
            $table->string('label');
            $table->text('guidance');
            $table->boolean('is_required')->default(false);
            $table->string('rule_group');
            $table->string('rule_mode');
            $table->unsignedSmallInteger('minimum_evidence_count')->default(1);
            $table->unsignedSmallInteger('maximum_evidence_count')->default(1);
            $table->json('definition_snapshot');
            $table->json('source_snapshot')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('learner_identity_verification_id')
                ->nullable()
                ->constrained('learner_identity_verifications', 'id', 'cvr_identity_fk')
                ->nullOnDelete();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users', 'id', 'cvr_requested_by_fk')
                ->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['connector_id', 'status']);
            $table->index(
                ['connector_id', 'requirement_key', 'superseded_at'],
                'connector_verification_requirements_lookup_index'
            );
        });

        Schema::create('connector_verification_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')
                ->constrained('connectors', 'id', 'cvd_connector_fk')
                ->restrictOnDelete();
            $table->foreignId('connector_verification_requirement_id')
                ->constrained('connector_verification_requirements', 'id', 'cvd_requirement_fk')
                ->restrictOnDelete();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users', 'id', 'cvd_uploaded_by_fk')
                ->nullOnDelete();
            $table->unsignedInteger('submission_round');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->char('content_sha256', 64);
            $table->string('issuer')->nullable();
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users', 'id', 'cvd_reviewed_by_fk')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('applicant_rejection_reason')->nullable();
            $table->text('internal_review_note')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['connector_id', 'status']);
            $table->index(
                ['connector_verification_requirement_id', 'status'],
                'connector_verification_documents_requirement_status_index'
            );
            $table->index(['connector_id', 'submission_round'], 'cvd_connector_round_idx');
            $table->index('content_sha256');
        });

        Schema::create('connector_verification_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')
                ->constrained('connectors', 'id', 'cva_connector_fk')
                ->restrictOnDelete();
            $table->foreignId('connector_verification_requirement_id')
                ->nullable()
                ->constrained('connector_verification_requirements', 'id', 'cva_requirement_fk')
                ->restrictOnDelete();
            $table->foreignId('connector_verification_document_id')
                ->nullable()
                ->constrained('connector_verification_documents', 'id', 'cva_document_fk')
                ->restrictOnDelete();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users', 'id', 'cva_actor_fk')
                ->nullOnDelete();
            $table->string('action');
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->unsignedInteger('submission_round')->default(0);
            $table->text('applicant_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['connector_id', 'action']);
            $table->index(['connector_id', 'submission_round'], 'cva_connector_round_idx');
        });

        Schema::table('connector_reviews', function (Blueprint $table): void {
            $table->string('reason_code')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedInteger('submission_round')->nullable();
            $table->index(['connector_id', 'submission_round'], 'connector_reviews_round_index');
        });

        $this->backfillLegacyVerified();
    }

    public function backfillLegacyVerified(): void
    {
        DB::table('connectors')
            ->where('status', 'verified')
            ->whereNull('verification_status')
            ->update([
                'verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED,
            ]);
    }

    public function down(): void
    {
        foreach ([
            'connector_verification_audits',
            'connector_verification_documents',
            'connector_verification_requirements',
        ] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new \RuntimeException('Cannot reverse Connector verification schema after evidence has been stored.');
            }
        }

        Schema::dropIfExists('connector_verification_audits');
        Schema::dropIfExists('connector_verification_documents');
        Schema::dropIfExists('connector_verification_requirements');

        Schema::table('connector_reviews', function (Blueprint $table): void {
            $table->dropIndex('connector_reviews_round_index');
            $table->dropColumn(['reason_code', 'internal_notes', 'submission_round']);
        });

        Schema::table('connectors', function (Blueprint $table): void {
            DB::statement('DROP INDEX connectors_identity_status_index ON connectors');
            $table->dropColumn([
                'organization_type',
                'logo_path',
                'verification_status',
                'current_submission_round',
                'verification_submitted_at',
            ]);
        });
    }
};
