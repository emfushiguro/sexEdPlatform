<?php

namespace Tests\Unit\Services;

use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayMongoPaymentLinkServiceTest extends TestCase
{
    public function test_create_checkout_session_sends_line_items_urls_and_prioritized_methods(): void
    {
        config()->set('paymongo.secret_key', 'sk_test_example');
        config()->set('paymongo.public_key', 'pk_test_example');
        config()->set('paymongo.api_base_url', 'https://api.paymongo.com/v1');
        config()->set('paymongo.payment_link.allowed_payment_method_types', ['gcash', 'paymaya', 'grab_pay', 'card']);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.test/cs_test_123',
                    ],
                ],
            ], 200),
        ]);

        $service = app(PayMongoPaymentLinkService::class);

        $service->createCheckoutSession(
            amount: 199.99,
            description: 'Unit test checkout',
            successUrl: 'https://example.test/payment/success',
            cancelUrl: 'https://example.test/payment/cancel',
            preferredPaymentMethod: 'card',
            lineItemName: 'Unit test line item',
        );

        Http::assertSent(function ($request) {
            $lineItems = data_get($request->data(), 'data.attributes.line_items', []);
            $types = data_get($request->data(), 'data.attributes.payment_method_types', []);
            $successUrl = data_get($request->data(), 'data.attributes.success_url');
            $cancelUrl = data_get($request->data(), 'data.attributes.cancel_url');

            return $request->url() === 'https://api.paymongo.com/v1/checkout_sessions'
                && $types === ['card', 'gcash', 'paymaya', 'grab_pay']
                && data_get($lineItems, '0.name') === 'Unit test line item'
                && data_get($lineItems, '0.amount') === 19999
                && $successUrl === 'https://example.test/payment/success'
                && $cancelUrl === 'https://example.test/payment/cancel';
        });
    }

    public function test_create_checkout_session_uses_default_methods_when_config_is_empty(): void
    {
        config()->set('paymongo.secret_key', 'sk_test_example');
        config()->set('paymongo.public_key', 'pk_test_example');
        config()->set('paymongo.api_base_url', 'https://api.paymongo.com/v1');
        config()->set('paymongo.payment_link.allowed_payment_method_types', []);

        Http::fake([
            'https://api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_234',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.test/cs_test_234',
                    ],
                ],
            ], 200),
        ]);

        $service = app(PayMongoPaymentLinkService::class);

        $service->createCheckoutSession(amount: 99.99, description: 'Unit test fallback methods');

        Http::assertSent(function ($request) {
            $types = data_get($request->data(), 'data.attributes.payment_method_types', []);
            $lineItems = data_get($request->data(), 'data.attributes.line_items', []);

            return $request->url() === 'https://api.paymongo.com/v1/checkout_sessions'
                && $types === ['card', 'gcash', 'paymaya', 'grab_pay']
                && data_get($lineItems, '0.amount') === 9999;
        });
    }
}
