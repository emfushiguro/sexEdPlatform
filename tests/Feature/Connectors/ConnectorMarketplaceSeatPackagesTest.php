<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModulePurchase;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleMarketplaceListingService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConnectorMarketplaceSeatPackagesTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_seller_can_publish_fixed_packages_without_changing_individual_price(): void
    {
        [$manager, , $module] = $this->createConnectorModulePaymentState(['price_amount' => 149]);
        $listing = app(ModuleMarketplaceListingService::class)->upsert($module, $manager, [
            'price_amount' => 2500, 'currency' => 'PHP', 'seat_capacity' => 10, 'action' => 'publish',
            'seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]],
        ]);

        $this->assertCount(2, $listing->packages());
        $this->assertEquals(5000, $listing->packageForSeats(25)['price_amount']);
        $this->assertEquals(149, $module->fresh()->price_amount);
    }

    public function test_duplicate_seat_quantities_are_rejected(): void
    {
        [$manager, , $module] = $this->createConnectorModulePaymentState();
        $this->expectException(ValidationException::class);
        app(ModuleMarketplaceListingService::class)->upsert($module, $manager, [
            'price_amount' => 2500, 'currency' => 'PHP', 'seat_capacity' => 10, 'action' => 'publish',
            'seat_packages' => [['seat_capacity' => 10, 'price_amount' => 1]],
        ]);
    }

    public function test_checkout_uses_selected_package_and_keeps_its_snapshot_after_seller_changes(): void
    {
        [$manager, $connector, , $listing, $previousPurchase] = $this->createConnectorModulePaymentState();
        $previousPurchase->update(['capacity_hold_expires_at' => now()->subMinute()]);
        $listing->update(['seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]]]);
        $this->mock(PayMongoPaymentLinkService::class, function ($mock): void {
            $mock->shouldReceive('createCheckoutSession')->once()->withArgs(fn (...$args) => (float) $args[0] === 5000.0)
                ->andReturn(['data' => ['id' => 'cs_tier', 'attributes' => ['checkout_url' => 'https://checkout.example.test/tier']]]);
        });

        $this->actingAs($manager)->get(route('connector.modules.purchase.checkout', [$connector, $listing, 'seat_capacity' => 25]))
            ->assertOk()->assertSee('5,000.00')->assertSee('value="25"', false);
        $this->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1', 'seat_capacity' => 25, 'price_amount' => 1,
        ])->assertRedirect('https://checkout.example.test/tier');

        $purchase = ConnectorModulePurchase::where('connector_id', $connector->id)->latest('id')->firstOrFail();
        $this->assertSame(25, $purchase->seat_capacity);
        $this->assertEquals(5000, $purchase->gross_amount);
        $listing->update(['seat_packages' => []]);
        $service = app(ConnectorModulePurchaseService::class);
        $this->assertTrue($service->completePayment($purchase->payment));
        $this->assertTrue($service->completePayment($purchase->payment));
        $this->assertSame(25, $purchase->fresh()->entitlement->seat_capacity);
        $this->assertEquals(5000, $purchase->fresh()->entitlement->gross_amount);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
    }

    public function test_unoffered_package_is_rejected_before_contacting_payment_provider(): void
    {
        [$manager, $connector, , $listing] = $this->createConnectorModulePaymentState();
        $this->mock(PayMongoPaymentLinkService::class)->shouldNotReceive('createCheckoutSession');
        $this->actingAs($manager)->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1', 'seat_capacity' => 999,
        ])->assertSessionHasErrors('seat_capacity');
    }

    public function test_largest_package_must_respect_shared_module_capacity(): void
    {
        [$manager, , $module] = $this->createConnectorModulePaymentState(['enrollment_limit' => 20]);
        $this->expectException(ValidationException::class);
        app(ModuleMarketplaceListingService::class)->upsert($module, $manager, [
            'price_amount' => 1000, 'currency' => 'PHP', 'seat_capacity' => 5, 'action' => 'publish',
            'seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]],
        ]);
    }

    public function test_initializing_checkout_cannot_be_overwritten_by_another_package(): void
    {
        [$manager, $connector, , $listing, $purchase] = $this->createConnectorModulePaymentState();
        $listing->update(['seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]]]);
        $paymentId = $purchase->payment_id;
        $this->mock(PayMongoPaymentLinkService::class)->shouldNotReceive('createCheckoutSession');

        $this->actingAs($manager)->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1', 'seat_capacity' => 25,
        ])->assertSessionHasErrors('seat_capacity');

        $this->assertSame($paymentId, $purchase->fresh()->payment_id);
        $this->assertSame(10, $purchase->fresh()->seat_capacity);
    }

    public function test_pending_package_can_be_resumed_when_its_own_hold_uses_all_capacity(): void
    {
        [$manager, $connector, , $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(['enrollment_limit' => 25]);
        $listing->update(['seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]]]);
        $purchase->update(['seat_capacity' => 25, 'gross_amount' => 5000]);
        $payment->update(['amount' => 5000, 'payment_details' => array_merge($payment->payment_details, ['checkout_url' => 'https://checkout.example.test/pending'])]);

        $this->actingAs($manager)->get(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->assertOk()->assertSee('Continue pending checkout')
            ->assertSee(route('connector.modules.purchase.checkout', [$connector, $listing, 'seat_capacity' => 25]), false);
        $listing->update(['seat_packages' => []]);
        $this->get(route('connector.modules.purchase.checkout', [$connector, $listing, 'seat_capacity' => 25]))
            ->assertOk()->assertSee('5,000.00')->assertSee('value="25"', false);
        $this->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1', 'seat_capacity' => 25,
        ])->assertRedirect('https://checkout.example.test/pending');
    }

    public function test_admin_can_save_and_remove_alternative_packages_through_existing_listing_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        [, , $module, $listing] = $this->createConnectorModulePaymentState(['created_by' => $admin->id]);
        $data = ['price_amount' => 2500, 'currency' => 'PHP', 'seat_capacity' => 10, 'action' => 'save', 'seat_packages_present' => '1'];
        $this->actingAs($admin)->put(route('admin.modules.marketplace.update', $module), [
            ...$data, 'seat_packages' => [['seat_capacity' => 25, 'price_amount' => 5000]],
        ])->assertSessionHasNoErrors();
        $this->assertCount(2, $listing->fresh()->packages());
        $this->put(route('admin.modules.marketplace.update', $module), $data)->assertSessionHasNoErrors();
        $this->assertSame([], $listing->fresh()->seat_packages);
        $this->assertCount(1, $listing->fresh()->packages());
    }

    public function test_invalid_package_price_and_default_price_precision_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        [, , $module] = $this->createConnectorModulePaymentState(['created_by' => $admin->id]);
        $this->actingAs($admin)->put(route('admin.modules.marketplace.update', $module), [
            'price_amount' => 2.129, 'currency' => 'PHP', 'seat_capacity' => 10, 'action' => 'save',
            'seat_packages' => [['seat_capacity' => 25, 'price_amount' => -1]],
        ])->assertSessionHasErrors(['price_amount', 'seat_packages.0.price_amount']);
    }

    public function test_repeated_same_package_request_cannot_overwrite_initializing_checkout(): void
    {
        [$manager, $connector, , $listing, $purchase] = $this->createConnectorModulePaymentState();
        $this->mock(PayMongoPaymentLinkService::class)->shouldNotReceive('createCheckoutSession');
        $paymentId = $purchase->payment_id;
        $this->actingAs($manager)->post(route('connector.modules.purchase.store', [$connector, $listing]), [
            'payment_method' => 'gcash', 'accept_terms' => '1', 'seat_capacity' => 10,
        ])->assertSessionHasErrors('seat_capacity');
        $this->assertSame($paymentId, $purchase->fresh()->payment_id);
    }
}
