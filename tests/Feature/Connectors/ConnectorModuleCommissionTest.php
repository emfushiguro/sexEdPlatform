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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConnectorModuleCommissionTest extends TestCase
{
    use ConnectorTestHelpers;

    #[DataProvider('configuredCommissions')]
    public function test_instructor_policy_is_snapshotted_and_later_policy_changes_do_not_rewrite_ledger(float $percent, string $expectedPercent, string $commission, string $net): void
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
        $policy = CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_INSTRUCTOR,
            'scope_id' => $instructor->id,
            'commission_percent' => $percent,
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
        $this->assertSame($expectedPercent, (string) $ledger->commission_percent_snapshot);
        $this->assertSame($commission, (string) $ledger->commission_amount);
        $this->assertSame($net, (string) $ledger->seller_net_amount);
        $this->assertSame($net, (string) $ledger->instructor_earnings_amount);
        $this->assertSame(1, ConnectorModuleEntitlement::query()->where('connector_module_purchase_id', $purchase->id)->count());
        $policy->update(['commission_percent' => 30]);
        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment($payment->fresh()));
        $this->assertSame($expectedPercent, (string) $ledger->fresh()->commission_percent_snapshot);
        $this->assertSame($net, (string) $ledger->fresh()->seller_net_amount);
        $this->assertSame(1, ModuleSaleLedger::where('payment_id', $payment->id)->count());
    }

    public static function configuredCommissions(): array
    {
        return [[10, '10.00', '250.00', '2250.00'], [17.5, '17.50', '437.50', '2062.50']];
    }
}
