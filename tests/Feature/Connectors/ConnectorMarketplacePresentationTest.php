<?php

namespace Tests\Feature\Connectors;

use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorMarketplacePresentationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_cards_use_learner_presentation_without_personal_progress_or_individual_price(): void
    {
        [$manager, $connector, $module] = $this->createConnectorModulePaymentState([
            'thumbnail' => 'modules/connector-cover.png',
            'access_type' => 'paid',
            'price_amount' => 149,
            'price_currency' => 'PHP',
        ]);

        $this->actingAs($manager)->get(route('connector.modules', $connector))
            ->assertOk()
            ->assertSee(asset('storage/modules/connector-cover.png'), false)
            ->assertSee('Created by:')
            ->assertSee('PHP 2,500.00')
            ->assertSee('10 seats')
            ->assertSee('View details')
            ->assertDontSee('PHP 149.00')
            ->assertDontSee('role="progressbar"', false)
            ->assertDontSee('Start Learning')
            ->assertDontSee('Last studied');
    }

    public function test_details_show_organization_license_information_and_shared_learner_hero(): void
    {
        [$manager, $connector, $module, $listing] = $this->createConnectorModulePaymentState([
            'thumbnail' => 'modules/connector-cover.png', 'min_age' => 13, 'max_age' => 17,
        ]);

        $this->actingAs($manager)->get(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->assertOk()
            ->assertSee(asset('storage/modules/connector-cover.png'), false)
            ->assertSee('About this module')
            ->assertSee('Organization license')
            ->assertSee('Eligible learners')
            ->assertSee('Non-refundable')
            ->assertSee('Assign seats after purchase')
            ->assertSee('all eligible members, a role, or selected members')
            ->assertDontSee('Start Learning')
            ->assertDontSee('Your progress')
            ->assertDontSee('unlocks after enrollment');
    }

    public function test_owned_connector_license_links_to_library_instead_of_offering_duplicate_purchase(): void
    {
        [$manager, $connector, , $listing, $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_presentation');

        foreach ([route('connector.modules', $connector), route('connector.modules.marketplace.show', [$connector, $listing])] as $url) {
            $this->actingAs($manager)->get($url)->assertOk()
                ->assertSee('View purchased license')
                ->assertDontSee('Purchase for Connector');
        }
    }

    public function test_capacity_exhaustion_disables_package_purchase_in_details(): void
    {
        [$manager, $connector, , $listing, $purchase] = $this->createConnectorModulePaymentState(['enrollment_limit' => 5]);
        $purchase->update(['capacity_hold_expires_at' => now()->subMinute()]);
        $response = $this->actingAs($manager)->get(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->assertOk()->assertSee('Not enough shared capacity');
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*disabled/s', $response->getContent());
    }
}
