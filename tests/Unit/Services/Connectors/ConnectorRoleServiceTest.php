<?php

namespace Tests\Unit\Services\Connectors;

use App\Models\User;
use App\Services\Connectors\ConnectorRoleService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorRoleServiceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_last_active_owner_cannot_be_removed_or_downgraded(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $membership = $connector->memberships()->first();
        $memberRole = $this->createCustomRole($connector, ['connector.view_subscription']);
        $service = app(ConnectorRoleService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->assertCanChangeMembershipRole($membership->load('role'), $memberRole);
    }
}
