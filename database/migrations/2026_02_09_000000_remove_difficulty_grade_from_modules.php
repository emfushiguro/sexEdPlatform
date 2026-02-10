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
        Schema::table('modules', function (Blueprint $table) {
            // Remove difficulty_level and grade_level as we use age_bracket instead
            if (Schema::hasColumn('modules', 'difficulty_level')) {
                $table->dropColumn('difficulty_level');
            }
            if (Schema::hasColumn('modules', 'grade_level')) {
                $table->dropColumn('grade_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                ->default('beginner')
                ->nullable();
            $table->string('grade_level')->nullable();
        });
    }
};
