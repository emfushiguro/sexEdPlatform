<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Notifications\Connectors\ConnectorModerationDecisionNotification;
use App\Notifications\Connectors\ConnectorVerificationActionRequiredNotification;
use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ConnectorVerificationNotificationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_submission_and_moderation_notifications_use_allowlisted_events_and_safe_mail_views(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $connector = $this->notificationConnector($owner);

        $submitted = new ConnectorApplicationSubmittedNotification($connector, 'learner', 'submitted');
        $resubmitted = new ConnectorApplicationSubmittedNotification($connector, 'learner', 'resubmitted');
        $approved = new ConnectorModerationDecisionNotification($connector, 'verified');

        $this->assertSame(['database'], $submitted->via($owner));
        $this->assertSame('connector_application_submitted', $submitted->toDatabase($owner)['type']);
        $this->assertSame('connector_application_resubmitted', $resubmitted->toDatabase($owner)['type']);
        $this->assertSame(route('connector.status', $connector), $resubmitted->toDatabase($owner)['action_url']);

        $this->assertSame(['mail', 'database'], $approved->via($owner));
        $mail = $approved->toMail($owner);
        $this->assertSame('emails.moderation-status', $mail->view);
        $this->assertSame(route('connector.status', $connector), $mail->viewData['actionUrl']);
        $this->assertArrayNotHasKey('filename', $approved->toDatabase($owner));
        $this->assertArrayNotHasKey('path', $approved->toDatabase($owner));
        $this->assertArrayNotHasKey('content_sha256', $approved->toDatabase($owner));
        $this->assertArrayNotHasKey('internal_note', $approved->toDatabase($owner));
    }

    public function test_action_required_notification_allowlists_events_audiences_and_safe_requirement_labels(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $connector = $this->notificationConnector($owner);

        $applicantNotification = new ConnectorVerificationActionRequiredNotification(
            $connector,
            'document_rejected',
            ['Rejected authorization document'],
            'learner',
        );
        $adminNotification = new ConnectorVerificationActionRequiredNotification(
            $connector,
            'supplemental_requirement',
            ['Current activity certification'],
            'admin',
        );

        $this->assertSame(['mail', 'database'], $applicantNotification->via($owner));
        $this->assertSame(['database'], $adminNotification->via($admin));
        $this->assertSame(
            route('connector.status', $connector),
            $applicantNotification->toDatabase($owner)['action_url'],
        );
        $this->assertSame(
            route('admin.connectors.show', $connector),
            $adminNotification->toDatabase($admin)['action_url'],
        );
        $this->assertSame(['Rejected authorization document'], $applicantNotification->toDatabase($owner)['requirement_labels']);
        $this->assertArrayNotHasKey('document', $applicantNotification->toDatabase($owner));
        $this->assertArrayNotHasKey('document_number', $applicantNotification->toDatabase($owner));
        $this->assertArrayNotHasKey('source_url', $applicantNotification->toDatabase($owner));

        $this->expectException(\InvalidArgumentException::class);
        new ConnectorVerificationActionRequiredNotification($connector, 'unsupported_event');
    }

    public function test_supplemental_requirement_notifies_after_commit_and_not_after_outer_rollback(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $connector = $this->notificationConnector($owner, ['organization_type' => 'other']);
        $definition = [
            'label' => 'Current activity certification',
            'guidance' => 'Provide current certification for the declared activity.',
            'evidence_category' => 'supporting_evidence',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 1,
            'accepted_mime_types' => ['application/pdf'],
            'metadata_fields' => ['issuer'],
        ];

        Notification::fake();
        DB::transaction(function () use ($connector, $admin, $definition): void {
            app(ConnectorVerificationService::class)->requestSupplementalRequirement(
                $connector,
                $admin,
                $definition,
                1,
            );
        });

        Notification::assertSentTo($owner, ConnectorVerificationActionRequiredNotification::class);
        Notification::assertSentTo($admin, ConnectorVerificationActionRequiredNotification::class);
        $this->assertDatabaseHas('connector_verification_requirements', [
            'connector_id' => $connector->id,
            'label' => 'Current activity certification',
        ]);

        Notification::fake();
        $rolledBackDefinition = $definition;
        $rolledBackDefinition['label'] = 'Rolled back certification';

        try {
            DB::transaction(function () use ($connector, $admin, $rolledBackDefinition): void {
                app(ConnectorVerificationService::class)->requestSupplementalRequirement(
                    $connector->fresh(),
                    $admin,
                    $rolledBackDefinition,
                    1,
                );

                throw new RuntimeException('rollback notification test');
            });
        } catch (RuntimeException) {
        }

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('connector_verification_requirements', [
            'connector_id' => $connector->id,
            'label' => 'Rolled back certification',
        ]);
    }

    public function test_rejected_document_is_not_emailed_until_resubmission_is_requested(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();

        Notification::fake();
        app(ConnectorVerificationService::class)->reviewDocument(
            $connector,
            $document,
            $admin,
            'reject',
            1,
            'Upload a clearer authorization.',
            'Internal review note must not be emailed.',
        );
        Notification::assertNothingSent();

        app(ConnectorVerificationService::class)->requestResubmission(
            $connector->fresh(),
            $admin,
            1,
            'Please replace the rejected authorization.',
            'Internal resubmission note must not be emailed.',
        );

        $captured = null;
        Notification::assertSentTo($owner, ConnectorVerificationActionRequiredNotification::class, function ($notification) use (&$captured): bool {
            $captured = $notification;

            return true;
        });

        $payload = $captured->toDatabase($owner);
        $this->assertSame('document_rejected', $payload['event']);
        $this->assertSame(['Representative authority'], $payload['requirement_labels']);
        $this->assertArrayNotHasKey('internal_note', $payload);
        $this->assertArrayNotHasKey('applicant_reason', $payload);
        $this->assertArrayNotHasKey('path', $payload);
        $this->assertSame(['mail', 'database'], $captured->via($owner));
    }

    private function notificationConnector(User $owner, array $overrides = []): Connector
    {
        return Connector::create(array_merge([
            'name' => 'Notification Connector',
            'slug' => 'notification-connector-'.str()->random(8),
            'category' => 'ngo',
            'organization_type' => 'ngo_nonprofit_foundation',
            'contact_number' => '09171234567',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'address_line' => '123 Community Road',
            'status' => 'pending',
            'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
            'current_submission_round' => 1,
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ], $overrides));
    }

    private function submitInitialApplication(User $owner): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload([
                'logo' => null,
            ]))
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }
}
