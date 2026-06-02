<?php

namespace Tests\Unit\Services\Connectors;

use App\Models\User;
use App\Services\Connectors\ConnectorEntitlementService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorEntitlementServiceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_service_returns_false_without_active_subscription(): void
    {
        $this->seedCaviteAddress();
        $connector = $this->createVerifiedConnector(User::factory()->create());

        $this->assertFalse(app(ConnectorEntitlementService::class)->hasEntitlement($connector, 'connector.modules'));
    }
}
