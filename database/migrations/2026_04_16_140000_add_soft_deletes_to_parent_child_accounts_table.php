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
        if (! Schema::hasColumn('parent_child_accounts', 'deleted_at')) {
            Schema::table('parent_child_accounts', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('parent_child_accounts', 'deleted_at')) {
            Schema::table('parent_child_accounts', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
