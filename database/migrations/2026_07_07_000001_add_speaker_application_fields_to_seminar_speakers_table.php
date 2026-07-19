<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_speakers', function (Blueprint $table): void {
            $table->text('application_motivation')->nullable();
            $table->text('application_expertise')->nullable();
            $table->text('application_experience')->nullable();
            $table->text('application_supporting_info')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seminar_speakers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'application_motivation',
                'application_expertise',
                'application_experience',
                'application_supporting_info',
                'review_note',
            ]);
        });
    }
};
