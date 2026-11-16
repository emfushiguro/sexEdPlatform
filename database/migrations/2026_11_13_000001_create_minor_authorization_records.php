<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minor_authorization_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_child_account_id')->constrained('parent_child_accounts')->restrictOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $table->string('consent_version', 32);
            $table->char('privacy_notice_hash', 64);
            $table->char('terms_hash', 64);
            $table->dateTime('accepted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Authorization history is retained.
    }
};
