<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_identity_verifications', function (Blueprint $table): void {
            $table->json('requested_corrections')->nullable();
            $table->text('correction_note')->nullable();
            $table->boolean('resubmission_allowed')->default(true);
            $table->unsignedBigInteger('persona_evidence_attempt_id')->nullable();
            $table->foreign('persona_evidence_attempt_id', 'learner_persona_evidence_fk')
                ->references('id')->on('persona_inquiries')->nullOnDelete();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('parent_identity_resubmission_allowed')->default(true);
        });
        Schema::table('learner_identity_audits', function (Blueprint $table): void {
            $table->json('correction_request')->nullable();
        });
    }

    public function down(): never
    {
        throw new LogicException('Verification correction and evidence provenance must be retained.');
    }
};
