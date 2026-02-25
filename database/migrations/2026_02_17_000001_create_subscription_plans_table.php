<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Basic, Premium, Organization
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->json('features'); // What's included
            $table->integer('trial_days')->default(0);
            $table->integer('max_users')->nullable(); // For organization plans
            $table->integer('max_modules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add plan_id to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('user_id')->constrained('subscription_plans');
            $table->decimal('price_paid', 10, 2)->nullable(); // Track actual price paid
            $table->date('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('grace_period_ends')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'price_paid', 'trial_ends_at', 'cancelled_at', 'cancellation_reason', 'auto_renew', 'grace_period_ends']);
        });
        
        Schema::dropIfExists('subscription_plans');
    }
};