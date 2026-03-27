<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'manual_processing'])->default('pending');
            $table->string('refund_id')->unique();
            $table->string('paymongo_refund_id')->nullable();
            $table->string('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('refund_details')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Add refunds relationship to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('payment_details');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
        
        Schema::dropIfExists('refunds');
    }
};