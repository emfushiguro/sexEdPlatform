<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorInvitation;
use App\Models\User;
use App\Services\Connectors\ConnectorInvitationService;
use Tests\TestCase;

class ConnectorInvitationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_owner_can_invite_existing_user_and_invited_user_can_accept_or_reject(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $target = User::factory()->create(['email' => 'invitee@example.test']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $this->actingAs($owner)->post(route('connector.invitations.store', $connector), [
            'email' => $target->email,
            'connector_role_id' => $role->id,
        ])->assertRedirect();

        $invitation = ConnectorInvitation::first();
        $this->assertSame('pending', $invitation->status);

        $this->actingAs($target)->post(route('connector.invitations.accept', [$connector, $invitation]))->assertRedirect(route('connector.dashboard', $connector));
        $this->assertTrue($connector->memberships()->where('user_id', $target->id)->where('status', 'active')->exists());

        $rejectTarget = User::factory()->create(['email' => 'reject@example.test']);
        $rejectInvitation = app(ConnectorInvitationService::class)->invite($connector, $owner, $rejectTarget, $role);

        $this->actingAs($rejectTarget)->post(route('connector.invitations.reject', [$connector, $rejectInvitation]))->assertRedirect(route('connector.status', $connector));
        $this->assertSame('rejected', $rejectInvitation->fresh()->status);
    }

    public function test_member_without_invite_permission_cannot_invite_and_last_owner_removal_is_blocked(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $target = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $limitedRole = $this->createCustomRole($connector, ['connector.view_subscription']);
        $connector->memberships()->create([
            'user_id' => $member->id,
            'connector_role_id' => $limitedRole->id,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->actingAs($member)->post(route('connector.invitations.store', $connector), [
            'email' => $target->email,
            'connector_role_id' => $limitedRole->id,
        ])->assertSessionHasErrors('email');

        $ownerMembership = $connector->memberships()->where('user_id', $owner->id)->first();
        $this->actingAs($owner)->delete(route('connector.members.destroy', [$connector, $ownerMembership]))
            ->assertSessionHasErrors('member');
    }
}
