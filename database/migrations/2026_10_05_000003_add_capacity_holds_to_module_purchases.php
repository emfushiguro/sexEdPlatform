<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_purchases', function (Blueprint $table): void {
            $table->timestamp('capacity_hold_expires_at')->nullable()->after('metadata');
            $table->index(['status', 'capacity_hold_expires_at'], 'idx_module_purchases_capacity_hold');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('module_purchases', 'capacity_hold_expires_at')
            && DB::table('module_purchases')->whereNotNull('capacity_hold_expires_at')->exists()) {
            throw new RuntimeException('Refusing to remove active module capacity-hold history.');
        }

        Schema::table('module_purchases', function (Blueprint $table): void {
            $table->dropIndex('idx_module_purchases_capacity_hold');
            $table->dropColumn('capacity_hold_expires_at');
        });
    }
};
