<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adult_dependent_authority_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_child_account_id')->constrained('parent_child_accounts', indexName: 'adult_authority_link_fk')->restrictOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users', indexName: 'adult_authority_guardian_fk')->restrictOnDelete();
            $table->string('document_path');
            $table->string('document_mime', 100);
            $table->char('document_hash', 64);
            $table->char('dependent_identity_hash', 64);
            $table->string('status', 16)->default('pending');
            $table->string('policy_version', 32);
            $table->char('privacy_notice_hash', 64);
            $table->char('terms_hash', 64);
            $table->timestamp('authorized_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users', indexName: 'adult_authority_reviewer_fk')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['parent_child_account_id', 'status'], 'adult_authority_link_status_idx');
        });

        Schema::table('dependent_adult_authorization_records', function (Blueprint $table): void {
            $table->string('authorization_method', 24)->default('self');
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users', indexName: 'adult_auth_actor_fk')->restrictOnDelete();
            $table->foreignId('authority_request_id')->nullable()->constrained('adult_dependent_authority_requests', indexName: 'adult_auth_request_fk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Authorization evidence is retained as history.
    }
};
