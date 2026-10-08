<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
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

        Notification::assertSentTo($owner, ConnectorApplicationSubmittedNotification::class, function ($notification, $channels) use ($owner, $connector): bool {
            return $channels === ['database']
                && $notification->toDatabase($owner)['type'] === 'connector_application_submitted'
                && $notification->toDatabase($owner)['action_url'] === route('connector.status', $connector);
        });
        Notification::assertSentTo($admin, ConnectorApplicationSubmittedNotification::class);

        foreach ($connector->verificationRequirements as $requirement) {
            if ($requirement->requirement_key === 'activity_specific_authority') {
                $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE]);

                continue;
            }

            $requirement->documents()->update([
                'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
            ]);
            $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        }

        $this->actingAs($admin)->post(route('admin.connectors.approve', $connector), [
            'submission_round' => 1,
            'checklist' => [
                'organization_identity_established' => '1',
                'regulatory_evidence_resolved' => '1',
                'representative_identity_verified' => '1',
                'representative_authority_established' => '1',
                'required_evidence_complete' => '1',
                'application_consistent' => '1',
            ],
        ]);
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
