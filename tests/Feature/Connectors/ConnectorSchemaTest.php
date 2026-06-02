<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorInvitation;
use App\Models\ConnectorReview;
use App\Models\User;
use App\Services\Connectors\ConnectorRoleService;
use Tests\TestCase;

class ConnectorSchemaTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_domain_records_and_relationships_persist(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create();

        $connector = Connector::create([
            ...$this->connectorPayload(),
            'slug' => 'cavite-youth-health-network',
            'status' => 'pending',
            'created_by' => $user->id,
            'primary_representative_user_id' => $user->id,
        ]);

        $role = app(ConnectorRoleService::class)->createDefaultOwnerRole($connector);
        $membership = $connector->memberships()->create([
            'user_id' => $user->id,
            'connector_role_id' => $role->id,
            'status' => 'pending',
        ]);
        $invitation = ConnectorInvitation::create([
            'connector_id' => $connector->id,
            'connector_role_id' => $role->id,
            'invited_user_id' => $user->id,
            'invited_by' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
        ]);
        $review = ConnectorReview::create([
            'connector_id' => $connector->id,
            'to_status' => 'pending',
            'reason' => 'Submitted.',
        ]);

        $this->assertNull($connector->organization_email);
        $this->assertTrue($role->permissions()->where('permission_key', 'connector.manage_members')->exists());
        $this->assertTrue($connector->memberships->contains($membership));
        $this->assertTrue($connector->invitations->contains($invitation));
        $this->assertTrue($connector->reviews->contains($review));
        $this->assertTrue($user->ownedConnectors->contains($connector));
        $this->assertTrue($user->connectorMemberships->contains($membership));
    }
}
