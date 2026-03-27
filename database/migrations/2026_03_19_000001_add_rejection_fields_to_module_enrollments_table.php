<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('module_enrollments', function (Blueprint $table) {
            $table->string('rejection_reason_code')->nullable()->after('status');
            $table->text('rejection_reason_note')->nullable()->after('rejection_reason_code');
            $table->foreignId('rejected_by_instructor_id')
                ->nullable()
                ->after('rejection_reason_note')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_instructor_id');

            $table->index(['status', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('module_enrollments', function (Blueprint $table) {
            $table->dropIndex(['status', 'module_id']);
            $table->dropConstrainedForeignId('rejected_by_instructor_id');
            $table->dropColumn([
                'rejection_reason_code',
                'rejection_reason_note',
                'rejected_at',
            ]);
        });
    }
};
