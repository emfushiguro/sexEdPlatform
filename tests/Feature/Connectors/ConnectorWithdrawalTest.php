<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use Tests\TestCase;

class ConnectorWithdrawalTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_pending_connector_application_can_be_withdrawn_by_owner(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = Connector::create([
            ...$this->connectorPayload(),
            'slug' => 'pending-withdrawal',
            'status' => 'pending',
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('connector.status', $connector))
            ->assertOk()
            ->assertSee('Withdraw Application');

        $this->actingAs($owner)
            ->post(route('connector.withdraw', $connector))
            ->assertRedirect(route('connector.status', $connector));

        $this->assertSame('withdrawn', $connector->fresh()->status);
        $this->assertTrue($connector->reviews()->where('to_status', 'withdrawn')->exists());
    }

    public function test_only_pending_connector_applications_can_be_withdrawn(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);

        $this->actingAs($owner)
            ->post(route('connector.withdraw', $connector))
            ->assertForbidden();

        $this->assertSame('verified', $connector->fresh()->status);
    }
}
