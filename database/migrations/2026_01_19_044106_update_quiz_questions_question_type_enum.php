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
        DB::statement("UPDATE quiz_questions SET question_type = 'multiple_choice' WHERE question_type = 'identification'");
        
        // Change enum to support our 3 types
        DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select') DEFAULT 'multiple_choice'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'identification') DEFAULT 'multiple_choice'");
    }
};
