<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add province_code to learner_profiles for better location tracking
     */
    public function up(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            // Add province_code column before city_code
            $table->string('province_code', 10)->nullable()->after('birthdate');
            
            // Add foreign key constraint
            $table->foreign('province_code')
                  ->references('code')
                  ->on('provinces')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->dropForeign(['province_code']);
            $table->dropColumn('province_code');
        });
    }
};
