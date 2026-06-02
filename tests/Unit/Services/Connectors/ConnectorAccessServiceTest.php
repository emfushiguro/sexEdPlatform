<?php

namespace Tests\Unit\Services\Connectors;

use App\Models\Connector;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorRoleService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorAccessServiceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_permission_catalog_and_workspace_access_are_connector_scoped(): void
    {
        $this->seedCaviteAddress();
        $service = app(ConnectorAccessService::class);
        $roles = app(ConnectorRoleService::class);
        $user = User::factory()->create();

        $this->assertContains('connector.manage_members', $roles->allPermissionKeys());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $roles->validatePermissionKeys(['access admin panel']);
    }

    public function test_pending_connector_denies_and_verified_active_membership_allows_workspace(): void
    {
        $this->seedCaviteAddress();
        $service = app(ConnectorAccessService::class);
        $user = User::factory()->create();
        $connector = Connector::create([
            ...$this->connectorPayload(),
            'slug' => 'pending-connector',
            'status' => 'pending',
            'created_by' => $user->id,
            'primary_representative_user_id' => $user->id,
        ]);
        $role = app(ConnectorRoleService::class)->createDefaultOwnerRole($connector);
        $connector->memberships()->create(['user_id' => $user->id, 'connector_role_id' => $role->id, 'status' => 'pending']);

        $this->assertFalse($service->canAccessWorkspace($user, $connector));

        $connector->update(['status' => 'verified']);
        $connector->memberships()->update(['status' => 'active', 'accepted_at' => now()]);

        $this->assertTrue($service->canAccessWorkspace($user, $connector->fresh()));
        $this->assertTrue($service->hasPermission($user, $connector->fresh(), 'connector.manage_members'));
    }
}
