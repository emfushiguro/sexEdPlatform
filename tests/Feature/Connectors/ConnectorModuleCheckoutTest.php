<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\ConnectorModuleEntitlement;
use App\Models\ConnectorModulePurchase;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use Mockery\MockInterface;
use Tests\TestCase;

class ConnectorModuleCheckoutTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_manager_can_start_a_seat_package_checkout_without_assigning_members(): void
    {
        [$manager, $connector, $listing] = $this->createCheckoutFixture();

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_connector_module_1',
                        'attributes' => ['checkout_url' => 'https://checkout.example.test/org-module'],
                    ],
                ]);
        });

        $this->actingAs($manager)->post(
            route('connector.modules.purchase.store', [$connector, $listing]),
            ['payment_method' => 'gcash', 'accept_terms' => '1'],
        )->assertRedirect('https://checkout.example.test/org-module');

        $this->assertDatabaseHas('connector_module_purchases', [
            'connector_id' => $connector->id,
            'module_id' => $listing->module_id,
            'status' => ConnectorModulePurchase::STATUS_PENDING,
            'gross_amount' => '2500.00',
            'currency' => 'PHP',
            'seat_capacity' => 10,
            'terms_version' => '2026-10-05',
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $manager->id,
            'status' => PaymentStatus::Pending->value,
            'amount' => '2500.00',
        ]);
        $this->assertDatabaseCount('connector_module_entitlements', 0);
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
    }

    public function test_non_manager_cannot_checkout_and_terms_are_required(): void
    {
        [$manager, $connector, $listing] = $this->createCheckoutFixture();
        $member = $this->createAdultConnectorMember($connector);

        $this->actingAs($member)
            ->post(route('connector.modules.purchase.store', [$connector, $listing]), [
                'payment_method' => 'gcash',
                'accept_terms' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('connector.modules.purchase.store', [$connector, $listing]), [
                'payment_method' => 'gcash',
            ])
            ->assertSessionHasErrors('accept_terms');
    }

    public function test_provider_failure_keeps_history_but_releases_the_hold(): void
    {
        [$manager, $connector, $listing] = $this->createCheckoutFixture();

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andThrow(new \RuntimeException('provider unavailable'));
        });

        $this->actingAs($manager)
            ->from(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->post(route('connector.modules.purchase.store', [$connector, $listing]), [
                'payment_method' => 'gcash',
                'accept_terms' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('connector_module_purchases', [
            'connector_id' => $connector->id,
            'status' => ConnectorModulePurchase::STATUS_FAILED,
            'capacity_hold_expires_at' => null,
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $manager->id,
            'status' => PaymentStatus::Failed->value,
        ]);
    }

    private function createCheckoutFixture(): array
    {
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $module = Module::factory()->create([
            'created_by' => User::factory()->create(['role' => 'admin'])->id,
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => 'approved',
            'title' => 'Connector Safety Module',
        ]);
        $listing = ModuleMarketplaceListing::create([
            'module_id' => $module->id,
            'seller_type' => 'platform',
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 10,
        ]);

        return [$manager, $connector, $listing];
    }
}
