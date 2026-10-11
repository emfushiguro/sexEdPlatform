<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_marketplace_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->string('seller_type', 32);
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->decimal('price_amount', 10, 2);
            $table->char('currency', 3);
            $table->unsignedInteger('seat_capacity');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('unpublished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unpublished_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();

            $table->unique('module_id', 'uq_module_marketplace_listings_module');
            $table->index(['status', 'seller_type'], 'idx_module_marketplace_listings_status_seller');
        });

        Schema::create('connector_module_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')->constrained('connectors')->restrictOnDelete();
            $table->unsignedBigInteger('module_marketplace_listing_id');
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->foreignId('purchased_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->decimal('gross_amount', 10, 2);
            $table->char('currency', 3);
            $table->unsignedInteger('seat_capacity');
            $table->string('seller_type_snapshot', 32);
            $table->unsignedBigInteger('seller_user_id_snapshot')->nullable();
            $table->string('module_title_snapshot');
            $table->string('terms_version', 64);
            $table->timestamp('terms_accepted_at');
            $table->timestamp('capacity_hold_expires_at')->nullable();
            $table->timestamp('provider_paid_at')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['connector_id', 'module_id'], 'uq_connector_module_purchase');
            $table->index(['status', 'capacity_hold_expires_at'], 'idx_connector_module_purchase_hold');
            $table->index(['connector_id', 'status'], 'idx_connector_module_purchase_connector_status');
            $table->foreign('module_marketplace_listing_id', 'fk_cmp_listing')
                ->references('id')
                ->on('module_marketplace_listings')
                ->restrictOnDelete();
        });

        Schema::create('connector_module_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')->constrained('connectors')->restrictOnDelete();
            $table->unsignedBigInteger('connector_module_purchase_id');
            $table->unsignedBigInteger('module_marketplace_listing_id');
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->string('status', 32)->default('active');
            $table->decimal('gross_amount', 10, 2);
            $table->char('currency', 3);
            $table->unsignedInteger('seat_capacity');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('status_reason')->nullable();
            $table->timestamps();

            $table->unique('connector_module_purchase_id', 'uq_connector_module_entitlement_purchase');
            $table->unique(['connector_id', 'module_id'], 'uq_connector_module_entitlement');
            $table->index(['connector_id', 'status'], 'idx_connector_module_entitlement_connector_status');
            $table->foreign('connector_module_purchase_id', 'fk_cme_purchase')
                ->references('id')
                ->on('connector_module_purchases')
                ->restrictOnDelete();
            $table->foreign('module_marketplace_listing_id', 'fk_cme_listing')
                ->references('id')
                ->on('module_marketplace_listings')
                ->restrictOnDelete();
        });

        Schema::create('connector_module_access_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('connector_module_entitlement_id');
            $table->string('target_type', 32);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('rule_key', 128);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['connector_module_entitlement_id', 'rule_key'], 'uq_connector_module_rule_key');
            $table->index(['target_type', 'target_id'], 'idx_connector_module_rule_target');
            $table->foreign('connector_module_entitlement_id', 'fk_cmar_entitlement')
                ->references('id')
                ->on('connector_module_entitlements')
                ->restrictOnDelete();
        });

        Schema::create('connector_module_seat_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('connector_module_entitlement_id');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('connector_membership_id')->nullable();
            $table->unsignedBigInteger('connector_module_access_rule_id')->nullable();
            $table->string('source_type', 32);
            $table->string('status', 32);
            $table->string('inactive_reason', 64)->nullable();
            $table->boolean('consumes_seat')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['connector_module_entitlement_id', 'user_id'], 'uq_connector_module_assignment_user');
            $table->index(['connector_module_entitlement_id', 'released_at'], 'idx_connector_module_assignment_seat');
            $table->index(['user_id', 'status'], 'idx_connector_module_assignment_user_status');
            $table->foreign('connector_module_entitlement_id', 'fk_cmsa_entitlement')
                ->references('id')
                ->on('connector_module_entitlements')
                ->restrictOnDelete();
            $table->foreign('connector_membership_id', 'fk_cmsa_membership')
                ->references('id')
                ->on('connector_memberships')
                ->nullOnDelete();
            $table->foreign('connector_module_access_rule_id', 'fk_cmsa_rule')
                ->references('id')
                ->on('connector_module_access_rules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach ([
            'connector_module_seat_assignments',
            'connector_module_access_rules',
            'connector_module_entitlements',
            'connector_module_purchases',
            'module_marketplace_listings',
        ] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Refusing to drop non-empty {$table}.");
            }
        }

        Schema::dropIfExists('connector_module_seat_assignments');
        Schema::dropIfExists('connector_module_access_rules');
        Schema::dropIfExists('connector_module_entitlements');
        Schema::dropIfExists('connector_module_purchases');
        Schema::dropIfExists('module_marketplace_listings');
    }
};
