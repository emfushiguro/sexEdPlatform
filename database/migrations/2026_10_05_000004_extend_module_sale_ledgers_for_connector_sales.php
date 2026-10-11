<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_sale_ledgers', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['module_purchase_id']);
            $table->dropForeign(['module_id']);
            $table->dropForeign(['instructor_id']);
            $table->dropForeign(['learner_id']);

            $table->foreignId('payment_id')->nullable()->change();
            $table->foreignId('module_purchase_id')->nullable()->change();
            $table->foreignId('module_id')->nullable()->change();
            $table->foreignId('instructor_id')->nullable()->change();
            $table->foreignId('learner_id')->nullable()->change();

            $table->string('sale_channel', 64)->default('individual')->after('id');
            $table->foreignId('connector_module_purchase_id')->nullable()->after('sale_channel');
            $table->foreignId('connector_id')->nullable()->after('connector_module_purchase_id');
            $table->foreignId('buyer_user_id')->nullable()->after('connector_id');
            $table->string('seller_type', 32)->nullable()->after('buyer_user_id');
            $table->foreignId('seller_user_id')->nullable()->after('seller_type');
            $table->string('seller_name_snapshot')->nullable()->after('seller_user_id');
            $table->unsignedInteger('seat_capacity_snapshot')->nullable()->after('seller_name_snapshot');
            $table->decimal('seller_net_amount', 10, 2)->nullable()->after('instructor_earnings_amount');
        });

        Schema::table('module_sale_ledgers', function (Blueprint $table): void {
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('module_purchase_id')->references('id')->on('module_purchases')->nullOnDelete();
            $table->foreign('module_id')->references('id')->on('modules')->nullOnDelete();
            $table->foreign('instructor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('learner_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('connector_module_purchase_id')
                ->references('id')
                ->on('connector_module_purchases')
                ->nullOnDelete();
            $table->foreign('connector_id')->references('id')->on('connectors')->nullOnDelete();
            $table->foreign('buyer_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('seller_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique('connector_module_purchase_id', 'uq_module_sale_ledgers_connector_purchase');
            $table->index(['sale_channel', 'occurred_at'], 'idx_module_sale_ledgers_channel_occurred');
            $table->index(['connector_id', 'occurred_at'], 'idx_module_sale_ledgers_connector_occurred');
        });

        DB::table('module_sale_ledgers')->update([
            'sale_channel' => 'individual',
        ]);
        DB::statement(
            'UPDATE module_sale_ledgers
             SET buyer_user_id = learner_id,
                 seller_type = CASE WHEN instructor_id IS NULL THEN NULL ELSE \'instructor\' END,
                 seller_user_id = instructor_id,
                 seller_net_amount = instructor_earnings_amount
             WHERE sale_channel = \'individual\''
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('module_sale_ledgers')
            && DB::table('module_sale_ledgers')->where('sale_channel', '!=', 'individual')->exists()) {
            throw new RuntimeException('Refusing to remove organization-channel ledger history.');
        }

        Schema::table('module_sale_ledgers', function (Blueprint $table): void {
            $table->dropUnique('uq_module_sale_ledgers_connector_purchase');
            $table->dropIndex('idx_module_sale_ledgers_channel_occurred');
            $table->dropIndex('idx_module_sale_ledgers_connector_occurred');
            $table->dropForeign(['connector_module_purchase_id']);
            $table->dropForeign(['connector_id']);
            $table->dropForeign(['buyer_user_id']);
            $table->dropForeign(['seller_user_id']);
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['module_purchase_id']);
            $table->dropForeign(['module_id']);
            $table->dropForeign(['instructor_id']);
            $table->dropForeign(['learner_id']);

            $table->dropColumn([
                'sale_channel',
                'connector_module_purchase_id',
                'connector_id',
                'buyer_user_id',
                'seller_type',
                'seller_user_id',
                'seller_name_snapshot',
                'seat_capacity_snapshot',
                'seller_net_amount',
            ]);

            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete()->change();
            $table->foreignId('module_purchase_id')->constrained('module_purchases')->cascadeOnDelete()->change();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete()->change();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete()->change();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete()->change();
        });
    }
};
