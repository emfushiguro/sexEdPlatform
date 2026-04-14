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
        if (! Schema::hasTable('role_transitions')) {
            return;
        }

        Schema::table('role_transitions', function (Blueprint $table): void {
            if (! Schema::hasColumn('role_transitions', 'custom_notes')) {
                $table->longText('custom_notes')->nullable()->after('reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('role_transitions') || ! Schema::hasColumn('role_transitions', 'custom_notes')) {
            return;
        }

        Schema::table('role_transitions', function (Blueprint $table): void {
            $table->dropColumn('custom_notes');
        });
    }
};
