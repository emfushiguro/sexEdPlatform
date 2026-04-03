<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_policies', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 32); // global or instructor
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('commission_percent', 5, 2);
            $table->string('tax_basis', 16)->default('gross');
            $table->string('refund_policy', 32)->default('disabled');
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id'], 'idx_commission_policies_scope');
            $table->index(['is_active', 'effective_from'], 'idx_commission_policies_active_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_policies');
    }
};
