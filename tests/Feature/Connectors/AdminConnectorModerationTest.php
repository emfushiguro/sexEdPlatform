<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use App\Services\Connectors\ConnectorRoleService;
use Tests\TestCase;

class AdminConnectorModerationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_can_approve_reject_and_suspend_connectors(): void
    {
        $this->seedCaviteAddress();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $owner = User::factory()->create(['role' => 'learner']);
        $connector = Connector::create([
            ...$this->connectorPayload(),
            'slug' => 'pending-admin-review',
            'status' => 'pending',
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);
        $role = app(ConnectorRoleService::class)->createDefaultOwnerRole($connector);
        $connector->memberships()->create(['user_id' => $owner->id, 'connector_role_id' => $role->id, 'status' => 'pending']);

        $this->actingAs($owner)->get(route('admin.connectors.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.connectors.index', ['status' => 'pending']))->assertOk()->assertSee($connector->name);

        $this->actingAs($admin)->post(route('admin.connectors.approve', $connector))->assertRedirect();
        $this->assertSame('verified', $connector->fresh()->status);
        $this->assertTrue($connector->memberships()->where('status', 'active')->exists());

        $this->actingAs($admin)->post(route('admin.connectors.suspend', $connector), ['reason' => 'Policy issue'])->assertRedirect();
        $this->assertSame('suspended', $connector->fresh()->status);

        $this->actingAs($admin)->post(route('admin.connectors.reject', $connector), ['reason' => 'Incomplete profile'])->assertRedirect();
        $this->assertSame('rejected', $connector->fresh()->status);
        $this->actingAs($owner)->get(route('connector.status', $connector))->assertOk()->assertSee('Incomplete profile');
    }

    public function test_reject_and_suspend_require_reason(): void
    {
        $this->seedCaviteAddress();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $connector = $this->createVerifiedConnector(User::factory()->create());

        $this->actingAs($admin)->from(route('admin.connectors.show', $connector))->post(route('admin.connectors.reject', $connector), [])
            ->assertSessionHasErrors('reason');
        $this->actingAs($admin)->from(route('admin.connectors.show', $connector))->post(route('admin.connectors.suspend', $connector), [])
            ->assertSessionHasErrors('reason');
    }
}
