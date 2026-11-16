<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->timestamp('adult_permissions_confirmed_at')->nullable();
        });

        Schema::create('dependent_adult_authorization_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dependent_user_id')->constrained('users', indexName: 'adult_auth_dependent_fk')->restrictOnDelete();
            $table->foreignId('parent_child_account_id')->nullable()->constrained('parent_child_accounts', indexName: 'adult_auth_relationship_fk')->restrictOnDelete();
            $table->string('policy_version', 32);
            $table->char('privacy_notice_hash', 64);
            $table->char('terms_hash', 64);
            $table->dateTime('accepted_at');
            $table->timestamps();
            $table->unique('dependent_user_id');
        });
    }

    public function down(): void
    {
        // Consent records are retained as authorization history.
    }
};
