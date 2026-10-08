<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_invitations', function (Blueprint $table): void {
            $table->foreignId('parent_child_account_id')
                ->nullable()
                ->after('child_user_id')
                ->constrained('parent_child_accounts')
                ->nullOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->foreignId('parent_child_invitation_id')
                ->nullable()
                ->after('status')
                ->constrained('parent_child_invitations')
                ->nullOnDelete();
            $table->unique('parent_child_invitation_id', 'conversations_guardian_invitation_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique('conversations_guardian_invitation_unique');
            $table->dropConstrainedForeignId('parent_child_invitation_id');
        });

        Schema::table('parent_child_invitations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_child_account_id');
        });
    }
};
