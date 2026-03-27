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
            // Drop old grade_level system
            $table->dropColumn('grade_level');
            
            // Add age bracket system
            $table->integer('min_age')->default(5)->after('description');
            $table->integer('max_age')->default(100)->after('min_age');
            $table->json('age_specific_content')->nullable()->after('max_age');
            
            $table->index(['min_age', 'max_age']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // Restore grade_level
            $table->enum('grade_level', ['grade_4_up', 'grade_6_up', 'grade_8_up', 'grade_10_up', 'adult_18_plus'])->nullable()->after('description');
            
            // Drop age bracket columns
            $table->dropColumn(['min_age', 'max_age', 'age_specific_content']);
        });
    }
};
