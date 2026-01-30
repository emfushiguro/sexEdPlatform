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
            if (Schema::hasColumn('learner_profiles', 'parent_email')) {
                $table->dropColumn('parent_email');
            }
            if (Schema::hasColumn('learner_profiles', 'parent_consent_required')) {
                $table->dropColumn('parent_consent_required');
            }
            if (Schema::hasColumn('learner_profiles', 'parent_consent_given')) {
                $table->dropColumn('parent_consent_given');
            }
            if (Schema::hasColumn('learner_profiles', 'parent_consent_at')) {
                $table->dropColumn('parent_consent_at');
            }
            if (Schema::hasColumn('learner_profiles', 'parent_consent_token')) {
                $table->dropIndex(['parent_consent_token']);
                $table->dropColumn('parent_consent_token');
            }
            if (Schema::hasColumn('learner_profiles', 'parent_consent_ip')) {
                $table->dropColumn('parent_consent_ip');
            }
        });
        
        // Drop parent_consent_logs table if exists
        Schema::dropIfExists('parent_consent_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_profiles', function (Blueprint $table) {
            $table->string('parent_email')->nullable();
            $table->boolean('parent_consent_required')->default(false);
            $table->boolean('parent_consent_given')->nullable();
            $table->timestamp('parent_consent_at')->nullable();
            $table->string('parent_consent_token')->nullable()->unique();
            $table->ipAddress('parent_consent_ip')->nullable();
        });
    }
};
