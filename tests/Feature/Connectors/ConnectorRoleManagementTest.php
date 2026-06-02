<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Tests\TestCase;

class ConnectorRoleManagementTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_owner_can_create_roles_with_catalog_permissions_only(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);

        $this->actingAs($owner)->post(route('connector.roles.store', $connector), [
            'name' => 'Member Manager',
            'permissions' => ['connector.manage_members'],
        ])->assertRedirect();

        $this->assertTrue($connector->roles()->where('name', 'Member Manager')->exists());

        $this->actingAs($owner)->post(route('connector.roles.store', $connector), [
            'name' => 'Bad Role',
            'permissions' => ['access admin panel'],
        ])->assertSessionHasErrors('permissions.0');
    }

    public function test_only_manage_roles_permission_can_manage_roles_and_owner_role_cannot_be_deleted(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $limitedRole = $this->createCustomRole($connector, ['connector.view_subscription']);
        $connector->memberships()->create([
            'user_id' => $member->id,
            'connector_role_id' => $limitedRole->id,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->actingAs($member)->get(route('connector.roles.index', $connector))->assertForbidden();

        $ownerRole = $connector->roles()->where('is_owner', true)->first();
        $this->actingAs($owner)->delete(route('connector.roles.destroy', [$connector, $ownerRole]))
            ->assertSessionHasErrors('role');
    }
}
