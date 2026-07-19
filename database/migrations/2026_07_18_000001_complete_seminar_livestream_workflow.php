<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('livestream_status')->default('scheduled')->index()->after('livestream_channel');
            $table->timestamp('livestream_started_at')->nullable()->after('livestream_status');
            $table->timestamp('livestream_ended_at')->nullable()->after('livestream_started_at');
        });

        Schema::table('seminar_speakers', function (Blueprint $table): void {
            $table->text('invitation_message')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('responded_at');
        });

        Schema::table('seminar_attendances', function (Blueprint $table): void {
            $table->string('role')->default('audience')->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_attendances', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::table('seminar_speakers', fn (Blueprint $table) => $table->dropColumn(['invitation_message', 'expires_at']));
        Schema::table('seminars', fn (Blueprint $table) => $table->dropColumn(['livestream_status', 'livestream_started_at', 'livestream_ended_at']));
    }
};
