<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connector_invitations', function (Blueprint $table): void {
            $table->dropUnique('conn_invite_user_status_unique');
            $table->index(['connector_id', 'invited_user_id', 'status'], 'conn_invite_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('connector_invitations', function (Blueprint $table): void {
            $table->dropIndex('conn_invite_user_status_idx');
            $table->unique(['connector_id', 'invited_user_id', 'status'], 'conn_invite_user_status_unique');
        });
    }
};
