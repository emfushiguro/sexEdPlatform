<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update learner_profiles to use PSGC foreign keys
     */
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('learner_profiles', 'municipality')) {
                $table->dropColumn('municipality');
            }
            if (Schema::hasColumn('learner_profiles', 'municipality_psgc')) {
                $table->dropColumn('municipality_psgc');
            }
            if (Schema::hasColumn('learner_profiles', 'barangay_psgc')) {
                $table->dropColumn('barangay_psgc');
            }
            
            // Add proper PSGC foreign keys
            $table->string('city_code', 10)->nullable()->after('birthdate');
            $table->string('barangay_code', 10)->nullable()->after('city_code');
            
            // Add foreign key constraints
            $table->foreign('city_code')
                  ->references('code')
                  ->on('cities')
                  ->onDelete('set null');
                  
            $table->foreign('barangay_code')
                  ->references('code')
                  ->on('barangays')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropForeign(['city_code']);
            $table->dropForeign(['barangay_code']);
            $table->dropColumn(['city_code', 'barangay_code']);
        });
    }
};
