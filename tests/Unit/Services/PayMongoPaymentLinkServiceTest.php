<?php

namespace Tests\Unit\Services;

use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayMongoPaymentLinkServiceTest extends TestCase
{
    public function test_create_payment_link_keeps_all_allowed_methods_and_prioritizes_preferred(): void
    {
        config()->set('paymongo.secret_key', 'sk_test_example');
        config()->set('paymongo.public_key', 'pk_test_example');
        config()->set('paymongo.api_base_url', 'https://api.paymongo.com/v1');
        config()->set('paymongo.payment_link.allowed_payment_method_types', ['gcash', 'paymaya', 'grab_pay', 'card']);

        Http::fake([
            'https://api.paymongo.com/v1/links' => Http::response([
                'data' => [
                    'id' => 'link_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.test/link_test_123',
                    ],
                ],
            ], 200),
        ]);

        $service = app(PayMongoPaymentLinkService::class);

        $service->createPaymentLink(
            amount: 199.99,
            description: 'Unit test checkout',
            preferredPaymentMethod: 'card',
        );

        Http::assertSent(function ($request) {
            $types = data_get($request->data(), 'data.attributes.payment_method_types', []);

            return $request->url() === 'https://api.paymongo.com/v1/links'
                && $types === ['card', 'gcash', 'paymaya', 'grab_pay'];
        });
    }

    public function test_create_payment_link_uses_default_methods_when_config_is_empty(): void
    {
        config()->set('paymongo.secret_key', 'sk_test_example');
        config()->set('paymongo.public_key', 'pk_test_example');
        config()->set('paymongo.api_base_url', 'https://api.paymongo.com/v1');
        config()->set('paymongo.payment_link.allowed_payment_method_types', []);

        Http::fake([
            'https://api.paymongo.com/v1/links' => Http::response([
                'data' => [
                    'id' => 'link_test_234',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.test/link_test_234',
                    ],
                ],
            ], 200),
        ]);

        $service = app(PayMongoPaymentLinkService::class);

        $service->createPaymentLink(
            amount: 99.99,
            description: 'Unit test fallback methods'
        );

        Http::assertSent(function ($request) {
            $types = data_get($request->data(), 'data.attributes.payment_method_types', []);

            return $types === ['gcash', 'paymaya', 'grab_pay', 'card'];
        });
    }
}
