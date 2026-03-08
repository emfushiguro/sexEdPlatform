<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update learner_profiles to use PSGC foreign keys
     */
    public function up(): void
    {
        // Drop indexes first (required for SQLite column drop compatibility)
        // Each column drop must be in a separate Schema::table call for SQLite
        try {
            Schema::table('learner_profiles', function (Blueprint $table) {
                $table->dropIndex(['municipality_psgc']);
            });
        } catch (\Exception $e) {
            // Index may not exist
        }

        try {
            Schema::table('learner_profiles', function (Blueprint $table) {
                $table->dropIndex(['barangay_psgc']);
            });
        } catch (\Exception $e) {
            // Index may not exist
        }

        Schema::table('learner_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('learner_profiles', 'municipality')) {
                $table->dropColumn('municipality');
            }
        });

        Schema::table('learner_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('learner_profiles', 'municipality_psgc')) {
                $table->dropColumn('municipality_psgc');
            }
        });

        Schema::table('learner_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('learner_profiles', 'barangay_psgc')) {
                $table->dropColumn('barangay_psgc');
            }
        });

        Schema::table('learner_profiles', function (Blueprint $table) {
            // Add proper PSGC foreign keys
            $table->string('city_code', 10)->nullable()->after('birthdate');
            $table->string('barangay_code', 10)->nullable()->after('city_code');
        });

        // Foreign keys must be added in a separate call for SQLite compatibility
        if (DB::getDriverName() === 'mysql') {
            Schema::table('learner_profiles', function (Blueprint $table) {
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
