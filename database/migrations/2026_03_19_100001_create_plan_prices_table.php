<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->enum('duration_mode', ['preset', 'custom'])->default('preset');
            $table->enum('duration_unit', ['day', 'week', 'month', 'year']);
            $table->unsignedInteger('duration_count');
            $table->string('duration_label');
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 3)->default('PHP');
            $table->unsignedInteger('compare_at_minor')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'is_active']);
            $table->index(['plan_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
