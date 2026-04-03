<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->string('rejection_reason_code')->nullable()->after('rejection_reason');
            $table->text('rejection_reason_note')->nullable()->after('rejection_reason_code');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->dropColumn(['rejection_reason_code', 'rejection_reason_note']);
        });
    }
};
