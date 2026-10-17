<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Models\ConnectorModulePurchase;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConnectorModuleCheckoutTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_manager_can_start_a_seat_package_checkout_without_assigning_members(): void
    {
        [$manager, $connector, $listing] = $this->createCheckoutFixture();

        $this->actingAs($manager)->get(route('connector.modules.purchase.checkout', [$connector, $listing]))
            ->assertOk()->assertSee('2,500.00')->assertSee('organization seat package');

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
        $purchase = ConnectorModulePurchase::firstOrFail();
        $this->assertNotNull($purchase->terms_accepted_at);
        $this->assertTrue($purchase->capacity_hold_expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));
        $this->assertSame($listing->module->title, $purchase->module_title_snapshot);
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

    #[DataProvider('inaccessibleStates')]
    public function test_unauthorized_users_cannot_browse_or_checkout(string $state): void
    {
        [$manager, $connector, $listing] = $this->createCheckoutFixture();
        if ($state === 'unverified connector') {
            $connector->update(['status' => 'pending']);
        } elseif ($state === 'inactive membership') {
            $connector->memberships()->where('user_id', $manager->id)->update(['status' => 'inactive']);
        }
        if ($state !== 'guest') {
            $this->actingAs($state === 'unrelated user' ? User::factory()->create() : $manager);
        }
        $browse = $this->get(route('connector.modules', $connector));
        $checkout = $this->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1',
        ]);
        if ($state === 'guest') {
            $browse->assertRedirect(route('login'));
            $checkout->assertRedirect(route('login'));
        } else {
            $browse->assertForbidden();
            $checkout->assertForbidden();
        }
        $this->assertDatabaseCount('connector_module_purchases', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public static function inaccessibleStates(): array
    {
        return [
            ['guest'], ['unrelated user'], ['inactive membership'], ['unverified connector'],
        ];
    }
}
