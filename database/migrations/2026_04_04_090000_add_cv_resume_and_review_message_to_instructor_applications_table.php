<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->string('cv_resume_path')->nullable()->after('clearance_path');
            $table->longText('review_message')->nullable()->after('rejection_reason_note');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_applications', function (Blueprint $table): void {
            $table->dropColumn(['cv_resume_path', 'review_message']);
        });
    }
};
