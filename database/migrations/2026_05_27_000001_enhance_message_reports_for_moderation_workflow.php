<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_reports', function (Blueprint $table): void {
            $table->string('reason_code', 64)->nullable()->after('status');
            $table->text('custom_reason')->nullable()->after('reason_code');
            $table->string('action_taken', 64)->nullable()->after('reason');
            $table->text('moderation_notes')->nullable()->after('action_taken');
            $table->foreignId('reviewed_by_admin_id')->nullable()->after('moderation_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_reports', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by_admin_id']);
            $table->dropColumn([
                'reason_code',
                'custom_reason',
                'action_taken',
                'moderation_notes',
                'reviewed_by_admin_id',
                'reviewed_at',
            ]);
        });
    }
};
