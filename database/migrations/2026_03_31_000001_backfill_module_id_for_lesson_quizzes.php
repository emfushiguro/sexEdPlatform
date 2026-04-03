<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            UPDATE quizzes
            INNER JOIN lessons ON lessons.id = quizzes.lesson_id
            SET quizzes.module_id = lessons.module_id,
                quizzes.updated_at = NOW()
            WHERE quizzes.lesson_id IS NOT NULL
              AND quizzes.module_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op to avoid clearing valid module associations.
    }
};
