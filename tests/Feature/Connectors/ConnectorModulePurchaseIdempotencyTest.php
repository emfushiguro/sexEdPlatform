<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModulePurchaseIdempotencyTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_repeated_completion_callback_creates_one_entitlement_and_one_ledger(): void
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
        $service = app(ConnectorModulePurchaseService::class);

        $this->assertTrue($service->completePayment($payment->fresh(), 'gcash', 'pay_idempotent_1'));
        $this->assertTrue($service->completePayment($payment->fresh(), 'gcash', 'pay_idempotent_1'));

        $this->assertDatabaseCount('connector_module_entitlements', 1);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
        $this->assertSame(ConnectorModulePurchase::STATUS_COMPLETED, $purchase->fresh()->status);
        $this->assertSame('completed', ModuleSaleLedger::query()->firstOrFail()->sale_status);
    }
}
