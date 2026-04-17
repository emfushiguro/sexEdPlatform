<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index(['status', 'archived_at', 'paid_at'], 'idx_payments_status_archived_paid_at');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->index(['status', 'processed_at'], 'idx_refunds_status_processed_at');
            });
        }

        if (Schema::hasTable('module_sale_ledgers')) {
            Schema::table('module_sale_ledgers', function (Blueprint $table) {
                $table->index(['sale_status', 'occurred_at'], 'idx_module_sale_ledgers_sale_status_occurred_at');
                $table->index(['occurred_at'], 'idx_module_sale_ledgers_occurred_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_status_archived_paid_at');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropIndex('idx_refunds_status_processed_at');
            });
        }

        if (Schema::hasTable('module_sale_ledgers')) {
            Schema::table('module_sale_ledgers', function (Blueprint $table) {
                $table->dropIndex('idx_module_sale_ledgers_sale_status_occurred_at');
                $table->dropIndex('idx_module_sale_ledgers_occurred_at');
            });
        }
    }
};
