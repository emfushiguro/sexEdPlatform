<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\CommissionPolicy;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleCommissionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_instructor_policy_is_snapshotted_and_later_policy_changes_do_not_rewrite_ledger(): void
    {
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);
        $listing = ModuleMarketplaceListing::query()->create([
            'module_id' => $module->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $instructor->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);
        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_INSTRUCTOR,
            'scope_id' => $instructor->id,
            'commission_percent' => 10,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subHour(),
            'updated_by' => $manager->id,
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
            'seller_type_snapshot' => 'instructor',
            'seller_user_id_snapshot' => $instructor->id,
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

        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment(
            $payment->fresh(),
            'gcash',
            'pay_instructor_1',
        ));

        $ledger = ModuleSaleLedger::query()
            ->where('payment_id', $payment->id)
            ->firstOrFail();
        $this->assertSame('10.00', (string) $ledger->commission_percent_snapshot);
        $this->assertSame('250.00', (string) $ledger->commission_amount);
        $this->assertSame('2250.00', (string) $ledger->seller_net_amount);
        $this->assertSame('2250.00', (string) $ledger->instructor_earnings_amount);
        $this->assertSame(0, ConnectorModuleEntitlement::query()->whereKey($purchase->id)->count());
    }
}
