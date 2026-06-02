<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use Tests\TestCase;

class ConnectorDashboardAccessTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_status_gated_workspace_access(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);

        $this->actingAs($owner)->get(route('connector.dashboard', $connector))->assertOk()->assertSee('Dashboard');
        $this->actingAs(User::factory()->create())->get(route('connector.dashboard', $connector))->assertForbidden();

        foreach (['pending', 'rejected', 'suspended'] as $status) {
            $connector->update(['status' => $status]);
            $this->actingAs($owner)->get(route('connector.dashboard', $connector))->assertRedirect(route('connector.status', $connector));
        }
    }
}
