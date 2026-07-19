<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorInvitation;
use App\Models\ConnectorMembershipRequest;
use App\Models\User;
use App\Notifications\Connectors\ConnectorMembershipRequestDecisionNotification;
use App\Notifications\Connectors\ConnectorMembershipRequestSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConnectorMembershipRequestTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_invited_user_can_open_connector_invitation_inbox_without_workspace_membership(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create(['role' => 'learner']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        ConnectorInvitation::create([
            'connector_id' => $connector->id,
            'connector_role_id' => $role->id,
            'invited_user_id' => $target->id,
            'invited_by' => $owner->id,
            'email' => $target->email,
            'status' => 'pending',
        ]);

        $this->actingAs($target)
            ->get(route('connectors.invitations.index'))
            ->assertOk()
            ->assertSee($connector->name);
    }

    public function test_user_can_request_membership_and_manager_can_approve(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $learner = User::factory()->create(['role' => 'learner']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $this->actingAs($learner)
            ->post(route('connectors.membership-requests.store', $connector))
            ->assertRedirect();

        $membershipRequest = ConnectorMembershipRequest::firstOrFail();
        $this->assertSame('pending', $membershipRequest->status);
        Notification::assertSentTo($owner, ConnectorMembershipRequestSubmittedNotification::class);

        $this->actingAs($owner)
            ->post(route('connector.membership-requests.approve', [$connector, $membershipRequest]))
            ->assertRedirect();

        $this->assertDatabaseHas('connector_memberships', [
            'connector_id' => $connector->id,
            'user_id' => $learner->id,
            'connector_role_id' => $connector->roles()->where('name', 'Member')->firstOrFail()->id,
            'status' => 'active',
        ]);
        Notification::assertSentTo($learner, ConnectorMembershipRequestDecisionNotification::class);
    }

    public function test_duplicate_membership_request_and_pending_invitation_are_blocked(): void
    {
        $owner = User::factory()->create();
        $learner = User::factory()->create(['role' => 'learner']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $this->actingAs($learner)
            ->post(route('connectors.membership-requests.store', $connector))
            ->assertRedirect();

        $this->actingAs($learner)
            ->post(route('connectors.membership-requests.store', $connector))
            ->assertSessionHasErrors('connector');

        $other = User::factory()->create(['role' => 'learner']);
        ConnectorInvitation::create([
            'connector_id' => $connector->id,
            'connector_role_id' => $role->id,
            'invited_user_id' => $other->id,
            'invited_by' => $owner->id,
            'email' => $other->email,
            'status' => 'pending',
        ]);

        $this->actingAs($other)
            ->post(route('connectors.membership-requests.store', $connector))
            ->assertSessionHasErrors('connector');
    }

    public function test_removed_member_can_request_and_rejoin(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $learner = User::factory()->create(['role' => 'learner']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $oldRequest = ConnectorMembershipRequest::create([
            'connector_id' => $connector->id,
            'user_id' => $learner->id,
            'status' => 'approved',
            'reviewed_by' => $owner->id,
            'reviewed_at' => now()->subDays(2),
        ]);

        $membership = $connector->memberships()->create([
            'user_id' => $learner->id,
            'connector_role_id' => $role->id,
            'status' => 'removed',
            'accepted_at' => now()->subDays(2),
            'removed_at' => now()->subDay(),
        ]);

        $this->actingAs($learner)
            ->post(route('connectors.membership-requests.store', $connector))
            ->assertRedirect();

        $newRequest = ConnectorMembershipRequest::where('id', '!=', $oldRequest->id)->firstOrFail();

        $this->actingAs($owner)
            ->post(route('connector.membership-requests.approve', [$connector, $newRequest]))
            ->assertRedirect();

        $membership->refresh();

        $this->assertSame('active', $membership->status);
        $this->assertNull($membership->removed_at);
        $this->assertSame('approved', $newRequest->fresh()->status);
    }

    public function test_manager_can_view_removed_members_page(): void
    {
        $owner = User::factory()->create();
        $learner = User::factory()->create(['name' => 'Removed Member', 'role' => 'learner']);
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $connector->memberships()->create([
            'user_id' => $learner->id,
            'connector_role_id' => $role->id,
            'status' => 'removed',
            'accepted_at' => now()->subDays(2),
            'removed_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('connector.members.removed', $connector))
            ->assertOk()
            ->assertSee('Removed Member')
            ->assertSee('Active Members');
    }
}
