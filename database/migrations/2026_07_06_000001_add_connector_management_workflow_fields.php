<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table) {
            $table->string('registration_approval_mode')->default('auto_approve')->after('capacity');
        });

        Schema::table('connector_invitations', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE seminar_registrants MODIFY status ENUM('pending','registered','rejected','attended','cancelled','no_show') NOT NULL DEFAULT 'registered'");
        }

        Schema::table('seminar_registrants', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('cancellation_reason');
            $table->timestamp('decided_at')->nullable()->after('rejection_reason');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seminar_registrants', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->dropColumn(['rejection_reason', 'decided_at', 'decided_by']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE seminar_registrants MODIFY status ENUM('registered','attended','cancelled','no_show') NOT NULL DEFAULT 'registered'");
        }

        Schema::table('connector_invitations', function (Blueprint $table) {
            $table->dropColumn('cancelled_at');
        });

        Schema::table('seminars', function (Blueprint $table) {
            $table->dropColumn('registration_approval_mode');
        });
    }
};
