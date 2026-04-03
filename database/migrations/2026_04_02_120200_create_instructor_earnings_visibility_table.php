<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_earnings_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_sale_ledger_id')->constrained('module_sale_ledgers')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('delete_reason')->nullable();
            $table->timestamps();

            $table->unique(['module_sale_ledger_id', 'instructor_id'], 'uq_instructor_earnings_visibility_row');
            $table->index(['instructor_id', 'deleted_at'], 'idx_instructor_earnings_visibility_instructor_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_earnings_visibility');
    }
};
