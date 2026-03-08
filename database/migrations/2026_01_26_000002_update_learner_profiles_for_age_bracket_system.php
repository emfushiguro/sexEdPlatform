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
        Schema::table('learner_profiles', function (Blueprint $table) {
            // Add birthdate-based system (nullable first, then set defaults)
            $table->date('birthdate')->nullable()->after('username');
            
            // Add PSGC codes for location
            $table->string('municipality_psgc', 10)->nullable()->after('municipality');
            $table->string('barangay', 100)->nullable()->after('municipality_psgc');
            $table->string('barangay_psgc', 10)->nullable()->after('barangay');
            
            // Parental consent fields (for under 13)
            $table->string('parent_email')->nullable()->after('barangay_psgc');
            $table->boolean('parent_consent_required')->default(false)->after('parent_email');
            $table->boolean('parent_consent_given')->nullable()->after('parent_consent_required');
            $table->timestamp('parent_consent_at')->nullable()->after('parent_consent_given');
            $table->string('parent_consent_token')->nullable()->unique()->after('parent_consent_at');
            $table->ipAddress('parent_consent_ip')->nullable()->after('parent_consent_token');
            
            // Add indexes
            $table->index('municipality_psgc');
            $table->index('barangay_psgc');
            $table->index('parent_consent_token');
        });
        
        // Set default birthdates for existing records (18 years old)
        $eighteenYearsAgo = now()->subYears(18)->toDateString();
        DB::table('learner_profiles')->whereNull('birthdate')->update([
            'birthdate' => $eighteenYearsAgo,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'birthdate',
                'municipality_psgc',
                'barangay',
                'barangay_psgc',
                'parent_email',
                'parent_consent_required',
                'parent_consent_given',
                'parent_consent_at',
                'parent_consent_token',
                'parent_consent_ip',
            ]);
        });
    }
};
