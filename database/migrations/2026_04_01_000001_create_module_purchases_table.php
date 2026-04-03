<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->string('status', 32)->default('pending');
            $table->timestamp('purchased_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'module_id'], 'idx_module_purchases_user_module');
            $table->index(['status', 'created_at'], 'idx_module_purchases_status_created');
            $table->unique(['user_id', 'module_id', 'status'], 'uq_module_purchases_user_module_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_purchases');
    }
};
