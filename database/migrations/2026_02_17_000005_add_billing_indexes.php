<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add performance indexes for billing-related queries
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['status', 'end_date'], 'idx_subscriptions_status_end_date');
            $table->index(['user_id', 'status'], 'idx_subscriptions_user_status');
            $table->index(['plan_id', 'status'], 'idx_subscriptions_plan_status');
            $table->index(['cancelled_at'], 'idx_subscriptions_cancelled_at');
            $table->index(['grace_period_ends'], 'idx_subscriptions_grace_period');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_payments_user_status');
            $table->index(['subscription_id', 'status'], 'idx_payments_subscription_status');
            $table->index(['paid_at'], 'idx_payments_paid_at');
            $table->index(['created_at', 'status'], 'idx_payments_created_status');
            $table->index(['method', 'status'], 'idx_payments_method_status');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->index(['last_login_at'], 'idx_users_last_login');
            }
            $table->index(['role', 'created_at'], 'idx_users_role_created');
        });

        // Add indexes to new tables
        if (Schema::hasTable('subscription_plans')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->index(['is_active', 'sort_order'], 'idx_plans_active_sort');
                $table->index(['slug'], 'idx_plans_slug');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_refunds_user_status');
                $table->index(['payment_id', 'status'], 'idx_refunds_payment_status');
                $table->index(['processed_at'], 'idx_refunds_processed_at');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_invoices_user_status');
                $table->index(['invoice_date'], 'idx_invoices_date');
                $table->index(['due_date', 'status'], 'idx_invoices_due_status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_status_end_date');
            $table->dropIndex('idx_subscriptions_user_status');
            $table->dropIndex('idx_subscriptions_plan_status');
            $table->dropIndex('idx_subscriptions_cancelled_at');
            $table->dropIndex('idx_subscriptions_grace_period');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_user_status');
            $table->dropIndex('idx_payments_subscription_status');
            $table->dropIndex('idx_payments_paid_at');
            $table->dropIndex('idx_payments_created_status');
            $table->dropIndex('idx_payments_method_status');
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropIndex('idx_users_last_login');
            }
            $table->dropIndex('idx_users_role_created');
        });

        if (Schema::hasTable('subscription_plans')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropIndex('idx_plans_active_sort');
                $table->dropIndex('idx_plans_slug');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropIndex('idx_refunds_user_status');
                $table->dropIndex('idx_refunds_payment_status');
                $table->dropIndex('idx_refunds_processed_at');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('idx_invoices_user_status');
                $table->dropIndex('idx_invoices_date');
                $table->dropIndex('idx_invoices_due_status');
            });
        }
    }
};