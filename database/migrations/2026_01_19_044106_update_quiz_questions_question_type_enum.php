<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing 'identification' types to 'multiple_choice' before changing enum
        DB::table('quiz_questions')
            ->where('question_type', 'identification')
            ->update(['question_type' => 'multiple_choice']);

        // For MySQL: use raw MODIFY COLUMN to change ENUM values
        // For SQLite (testing): column is already a string, no ENUM constraint to change
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select') DEFAULT 'multiple_choice'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'identification') DEFAULT 'multiple_choice'");
        }
    }
};
