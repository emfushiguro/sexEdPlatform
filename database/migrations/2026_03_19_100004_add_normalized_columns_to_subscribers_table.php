<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->foreignId('plan_price_id')->nullable()->after('plan_id')->constrained('plan_prices')->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->after('start_date');
            $table->timestamp('ends_at')->nullable()->after('end_date');
            $table->timestamp('grace_ends_at')->nullable()->after('grace_period_ends');
            $table->timestamp('cancel_at')->nullable()->after('grace_ends_at');
            $table->timestamp('canceled_at')->nullable()->after('cancel_at');
            $table->timestamp('next_billing_at')->nullable()->after('canceled_at');
            $table->string('source_provider')->nullable()->after('next_billing_at');
            $table->string('source_reference')->nullable()->after('source_provider');

            $table->index(['status', 'ends_at']);
            $table->index(['plan_price_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex(['status', 'ends_at']);
            $table->dropIndex(['plan_price_id', 'status']);
            $table->dropForeign(['plan_price_id']);
            $table->dropColumn([
                'plan_price_id',
                'starts_at',
                'ends_at',
                'grace_ends_at',
                'cancel_at',
                'canceled_at',
                'next_billing_at',
                'source_provider',
                'source_reference',
            ]);
        });
    }
};
