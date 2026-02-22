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
        // First, update the ENUM to include new question types
        DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification') DEFAULT 'multiple_choice'");
        
        // Then add new columns
        Schema::table('quiz_questions', function (Blueprint $table) {
            // For fill-in-the-blank and identification questions
            $table->text('acceptable_answers')->nullable()->after('question_text');
            $table->boolean('case_sensitive')->default(false)->after('acceptable_answers');
            
            // For fill_blank_select (word selection) - stores word bank as JSON array
            $table->json('word_bank')->nullable()->after('case_sensitive');
            
            // For identification questions with images
            $table->string('image_path')->nullable()->after('word_bank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn(['acceptable_answers', 'case_sensitive', 'word_bank', 'image_path']);
        });
        
        // Revert ENUM back to original types
        DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select') DEFAULT 'multiple_choice'");
    }
};
