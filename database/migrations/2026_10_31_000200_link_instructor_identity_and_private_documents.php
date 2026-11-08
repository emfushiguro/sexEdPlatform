<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persona_inquiries', function (Blueprint $table): void {
            $table->unsignedBigInteger('instructor_identity_verification_id')
                ->nullable()
                ->after('learner_identity_verification_id');
            $table->index(
                ['instructor_identity_verification_id'],
                'persona_inquiries_instructor_identity_idx',
            );
            $table->foreign('instructor_identity_verification_id', 'persona_inquiries_instructor_identity_fk')
                ->references('id')->on('instructor_identity_verifications')->nullOnDelete();
        });

        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->unsignedBigInteger('instructor_identity_verification_id')
                ->nullable()
                ->after('user_id');
            $table->index(
                ['instructor_identity_verification_id'],
                'instructor_apps_identity_idx',
            );
            $table->foreign('instructor_identity_verification_id', 'instructor_apps_identity_fk')
                ->references('id')->on('instructor_identity_verifications')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->string('document_disk', 32)->default('public')->after('submitted_at');
            $table->string('government_id_path')->nullable()->change();
        });

        DB::statement("ALTER TABLE instructor_applications MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): never
    {
        throw new LogicException(
            'Instructor application identity links and uploaded-document history must not be removed by rollback.'
        );
    }
};
