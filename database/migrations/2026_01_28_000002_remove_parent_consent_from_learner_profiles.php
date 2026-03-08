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
        // Drop all indexes on parent_consent_token first (must be separate for SQLite)
        if (Schema::hasColumn('learner_profiles', 'parent_consent_token')) {
            try {
                Schema::table('learner_profiles', function (Blueprint $table) {
                    $table->dropUnique(['parent_consent_token']);
                });
            } catch (\Exception $e) {
                // Unique index may not exist
            }
            try {
                Schema::table('learner_profiles', function (Blueprint $table) {
                    $table->dropIndex(['parent_consent_token']);
                });
            } catch (\Exception $e) {
                // Regular index may not exist
            }
        }

        // Each column drop must be in its own Schema::table call for SQLite compatibility
        $columns = ['parent_email', 'parent_consent_required', 'parent_consent_given', 'parent_consent_at', 'parent_consent_token', 'parent_consent_ip'];

        foreach ($columns as $column) {
            if (Schema::hasColumn('learner_profiles', $column)) {
                Schema::table('learner_profiles', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

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
