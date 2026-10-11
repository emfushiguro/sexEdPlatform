<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Services\RefundService;
use InvalidArgumentException;
use Tests\TestCase;

class ConnectorModuleRefundBoundaryTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_module_purchases_are_non_refundable_by_default(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            paymentOverrides: [
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-refundable');

        app(RefundService::class)->processRefund($payment->fresh());
    }
}
