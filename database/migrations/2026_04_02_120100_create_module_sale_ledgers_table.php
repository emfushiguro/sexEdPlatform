<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_sale_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->string('learner_name_snapshot')->nullable();
            $table->char('currency', 3)->default('PHP');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('basis_amount', 10, 2);
            $table->decimal('commission_percent_snapshot', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('instructor_earnings_amount', 10, 2);
            $table->string('tax_basis_snapshot', 16);
            $table->string('refund_policy_snapshot', 32)->default('disabled');
            $table->string('sale_status', 32)->default('completed');
            $table->string('payout_status', 32)->default('pending');
            $table->string('payout_batch_reference')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique('payment_id');
            $table->index(['instructor_id', 'occurred_at'], 'idx_module_sale_ledgers_instructor_occurred');
            $table->index(['module_id', 'occurred_at'], 'idx_module_sale_ledgers_module_occurred');
            $table->index(['payout_status', 'occurred_at'], 'idx_module_sale_ledgers_payout_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_sale_ledgers');
    }
};
