<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification', 'perspective_feedback') DEFAULT 'multiple_choice'");
        }

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->text('context_description')->nullable()->after('question_text');
            $table->boolean('allow_own_perspective')->default(false)->after('explanation');
            $table->text('perspective_prompt')->nullable()->after('allow_own_perspective');
            $table->unsignedSmallInteger('perspective_character_limit')->nullable()->after('perspective_prompt');
            $table->text('reflection_guide')->nullable()->after('perspective_character_limit');
        });

        Schema::table('quiz_options', function (Blueprint $table): void {
            $table->text('feedback')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_questions')->where('question_type', 'perspective_feedback')->exists()) {
            throw new \RuntimeException('Remove Perspective Feedback records before rolling back this migration.');
        }

        Schema::table('quiz_options', function (Blueprint $table): void {
            $table->dropColumn('feedback');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropColumn([
                'context_description',
                'allow_own_perspective',
                'perspective_prompt',
                'perspective_character_limit',
                'reflection_guide',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification') DEFAULT 'multiple_choice'");
        }
    }
};
