<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_mfa_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('session_fingerprint', 64);
            $table->string('credential_fingerprint', 64);
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('code_expires_at');
            $table->timestamp('login_expires_at');
            $table->timestamp('resend_available_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('delivery_failed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'admin_mfa_user_created_index');
            $table->index('login_expires_at', 'admin_mfa_login_expiry_index');
            $table->index('consumed_at', 'admin_mfa_consumed_index');
            $table->index('cancelled_at', 'admin_mfa_cancelled_index');
            $table->index('locked_at', 'admin_mfa_locked_index');
        });
    }

    public function down(): void
    {
        // Forward-only: retain authentication challenge history.
    }
};
