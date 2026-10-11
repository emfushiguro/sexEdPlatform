<?php

namespace Tests\Feature\Instructor;

use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\ModuleSaleLedger;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class InstructorConnectorModuleSalesTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorTestHelpers;

    public function test_instructor_earnings_shows_connector_channel_connector_buyer_and_seats(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Marketplace Seller']);
        $instructor->assignRole('instructor');
        $manager = User::factory()->create(['name' => 'Connector Buyer']);
        $connector = $this->createVerifiedConnector($manager);
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
            'access_type' => 'paid',
        ]);
        $listing = ModuleMarketplaceListing::query()->create([
            'module_id' => $module->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $instructor->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 8,
        ]);
        $purchase = ConnectorModulePurchase::query()->create([
            'connector_id' => $connector->id,
            'module_marketplace_listing_id' => $listing->id,
            'module_id' => $module->id,
            'purchased_by' => $manager->id,
            'status' => ConnectorModulePurchase::STATUS_COMPLETED,
            'gross_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 8,
            'seller_type_snapshot' => 'instructor',
            'seller_user_id_snapshot' => $instructor->id,
            'module_title_snapshot' => $module->title,
            'terms_version' => ConnectorModulePurchase::TERMS_VERSION,
            'terms_accepted_at' => now(),
            'purchased_at' => now(),
        ]);
        $payment = Payment::query()->create([
            'user_id' => $manager->id,
            'amount' => 2500,
            'method' => 'gcash',
            'status' => 'completed',
            'transaction_id' => 'CONN-EARN-1',
            'payment_details' => ['payment_scope' => 'connector_module_purchase'],
            'paid_at' => now(),
        ]);
        $purchase->update(['payment_id' => $payment->id]);
        $ledger = ModuleSaleLedger::query()->create([
            'payment_id' => $payment->id,
            'sale_channel' => ModuleSaleLedger::SALE_CHANNEL_CONNECTOR_MARKETPLACE,
            'connector_module_purchase_id' => $purchase->id,
            'connector_id' => $connector->id,
            'buyer_user_id' => $manager->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $instructor->id,
            'seller_name_snapshot' => $instructor->name,
            'seat_capacity_snapshot' => 8,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
            'currency' => 'PHP',
            'gross_amount' => 2500,
            'basis_amount' => 2500,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 250,
            'instructor_earnings_amount' => 2250,
            'seller_net_amount' => 2250,
            'tax_basis_snapshot' => 'gross',
            'refund_policy_snapshot' => 'disabled',
            'sale_status' => 'completed',
            'payout_status' => 'paid',
            'occurred_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.earnings.index'))
            ->assertOk()
            ->assertSee('Connector Marketplace', false)
            ->assertSee($connector->name, false)
            ->assertSee($manager->name, false)
            ->assertSee('8', false);

        $this->actingAs($instructor)
            ->get(route('instructor.earnings.show', $ledger))
            ->assertOk()
            ->assertSee('Connector Marketplace', false)
            ->assertSee($connector->name, false)
            ->assertSee('8', false);
    }
}
