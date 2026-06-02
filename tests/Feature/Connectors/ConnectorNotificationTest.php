<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Notifications\Connectors\ConnectorApplicationWithdrawnNotification;
use App\Notifications\Connectors\ConnectorInvitationReceivedNotification;
use App\Notifications\Connectors\ConnectorModerationDecisionNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConnectorNotificationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_submission_moderation_invitation_and_withdrawal_notifications_are_sent(): void
    {
        $this->seedCaviteAddress();
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $owner = User::factory()->create(['role' => 'learner']);
        $invitee = User::factory()->create(['email' => 'connector-invitee@example.test']);

        $this->actingAs($owner)->post(route('connectors.store'), $this->connectorPayload());
        $connector = Connector::first();

        Notification::assertSentTo($owner, ConnectorApplicationSubmittedNotification::class);
        Notification::assertSentTo($admin, ConnectorApplicationSubmittedNotification::class);

        $this->actingAs($admin)->post(route('admin.connectors.approve', $connector));
        Notification::assertSentTo($owner, ConnectorModerationDecisionNotification::class);

        $role = $this->createCustomRole($connector->fresh(), ['connector.view_subscription']);
        $this->actingAs($owner)->post(route('connector.invitations.store', $connector), [
            'email' => $invitee->email,
            'connector_role_id' => $role->id,
        ]);
        Notification::assertSentTo($invitee, ConnectorInvitationReceivedNotification::class);

        $pending = Connector::create([
            ...$this->connectorPayload(['name' => 'Withdrawal Notify']),
            'slug' => 'withdrawal-notify',
            'status' => 'pending',
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);
        $this->actingAs($owner)->post(route('connector.withdraw', $pending));
        Notification::assertSentTo($admin, ConnectorApplicationWithdrawnNotification::class);
    }
}
