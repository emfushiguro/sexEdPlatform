<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModulePurchaseCompletionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_platform_purchase_completes_without_assigning_members(): void
    {
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $module = Module::factory()->create([
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);
        $listing = ModuleMarketplaceListing::query()->create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);
        $purchase = ConnectorModulePurchase::query()->create([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $manager->id,
            'status' => ConnectorModulePurchase::STATUS_PENDING,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
            'seller_type_snapshot' => 'platform',
            'seller_user_id_snapshot' => null,
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'capacity_hold_expires_at' => now()->addMinutes(30),
        ]);
        $payment = Payment::query()->create([
            'user_id' => $manager->id,
            'subscription_id' => null,
            'amount' => 2500,
            'method' => 'gcash',
            'status' => PaymentStatus::Pending,
            'payment_details' => [
                'payment_scope' => 'connector_module_purchase',
                'connector_id' => $connector->id,
                'connector_module_purchase_id' => $purchase->id,
                'marketplace_listing_id' => $listing->id,
                'module_id' => $module->id,
            ],
        ]);
        $purchase->update(['payment_id' => $payment->id]);

        $listing->update(['status' => ModuleMarketplaceListing::STATUS_UNPUBLISHED]);

        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment(
            $payment->fresh(),
            'gcash',
            'pay_platform_1',
        ));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('connector_module_purchases', [
            'id' => $purchase->id,
            'status' => ConnectorModulePurchase::STATUS_COMPLETED,
            'gross_amount' => '2500.00',
            'seat_capacity' => 10,
        ]);
        $this->assertDatabaseHas('connector_module_entitlements', [
            'connector_module_purchase_id' => $purchase->id,
            'connector_id' => $connector->id,
            'module_id' => $module->id,
            'status' => ConnectorModuleEntitlement::STATUS_ACTIVE,
            'seat_capacity' => 10,
        ]);
        $this->assertDatabaseHas('module_sale_ledgers', [
            'payment_id' => $payment->id,
            'sale_channel' => 'connector_marketplace',
            'connector_id' => $connector->id,
            'gross_amount' => '2500.00',
            'commission_percent_snapshot' => '0.00',
            'commission_amount' => '0.00',
            'seller_net_amount' => '2500.00',
            'seat_capacity_snapshot' => 10,
        ]);
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
    }

    public function test_current_module_safety_invalidates_paid_checkout_without_creating_access(): void
    {
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $module = Module::factory()->create([
            'content_owner_type' => 'admin',
            'is_published' => false,
            'current_review_status' => 'rejected',
        ]);
        $listing = ModuleMarketplaceListing::query()->create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);
        $purchase = ConnectorModulePurchase::query()->create([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $manager->id,
            'status' => ConnectorModulePurchase::STATUS_PENDING,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
            'seller_type_snapshot' => 'platform',
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'capacity_hold_expires_at' => now()->subMinute(),
        ]);
        $payment = Payment::query()->create([
            'user_id' => $manager->id,
            'subscription_id' => null,
            'amount' => 2500,
            'method' => 'gcash',
            'status' => PaymentStatus::Pending,
            'payment_details' => [
                'payment_scope' => 'connector_module_purchase',
                'connector_id' => $connector->id,
                'connector_module_purchase_id' => $purchase->id,
                'marketplace_listing_id' => $listing->id,
                'module_id' => $module->id,
            ],
        ]);
        $purchase->update(['payment_id' => $payment->id]);

        $this->assertFalse(app(ConnectorModulePurchaseService::class)->completePayment(
            $payment->fresh(),
            'gcash',
            'pay_invalid_1',
        ));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('connector_module_purchases', [
            'id' => $purchase->id,
            'status' => ConnectorModulePurchase::STATUS_ATTENTION_REQUIRED,
        ]);
        $this->assertDatabaseCount('connector_module_entitlements', 0);
        $this->assertDatabaseCount('module_sale_ledgers', 0);
    }
}
