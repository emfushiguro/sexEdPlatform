<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use App\Notifications\Connectors\ConnectorVerificationActionRequiredNotification;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConnectorVerificationExpirationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_verification_expiration_preserves_marketplace_purchase_entitlement_and_membership(): void
    {
        Carbon::setTestNow('2026-10-07 01:00:00');
        Notification::fake();
        [, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState();
        $connector->update(['verification_status' => Connector::VERIFICATION_VERIFIED, 'current_submission_round' => 1]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_expiry_preservation');
        $memberships = $connector->memberships()->pluck('status', 'id')->all();
        $document = $this->document($connector, 'expired-marketplace-evidence', 'Expiring evidence', Carbon::parse('2026-10-07'), ['expires' => true]);

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();
        $this->assertSame(ConnectorVerificationDocument::STATUS_EXPIRED, $document->fresh()->status);
        $this->assertSame(Connector::VERIFICATION_REVERIFICATION_REQUIRED, $connector->fresh()->verification_status);
        $this->assertSame('verified', $connector->fresh()->status);
        $this->assertSame($memberships, $connector->memberships()->pluck('status', 'id')->all());
        $this->assertSame('completed', $purchase->fresh()->status);
        $this->assertSame('active', $purchase->fresh()->entitlement->status);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'completed']);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
    }

    use ConnectorTestHelpers;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_command_sends_configured_warnings_once(): void
    {
        Carbon::setTestNow('2026-10-07 01:00:00');
        Notification::fake();

        $owner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $connector = $this->connector($owner, 'verified', Connector::VERIFICATION_VERIFIED);

        $this->document($connector, 'expiring-30', 'Expiring document 30', Carbon::parse('2026-11-06'), [
            'expires' => true,
        ]);
        $this->document($connector, 'expiring-7', 'Expiring document 7', Carbon::parse('2026-10-14'), [
            'expires' => true,
        ]);

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();

        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_expiration_warning_30')->count());
        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_expiration_warning_7')->count());
        $this->assertSame(2, Notification::sent($owner, ConnectorVerificationActionRequiredNotification::class)->count());
        Notification::assertNotSentTo($admin, ConnectorVerificationActionRequiredNotification::class);

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();

        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_expiration_warning_30')->count());
        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_expiration_warning_7')->count());
        $this->assertSame(2, Notification::sent($owner, ConnectorVerificationActionRequiredNotification::class)->count());
        $this->assertSame('verified', $connector->fresh()->status);
        $this->assertSame(Connector::VERIFICATION_VERIFIED, $connector->fresh()->verification_status);
    }

    public function test_due_expiration_updates_verified_and_pending_boundaries_and_is_idempotent(): void
    {
        Carbon::setTestNow('2026-10-07 01:00:00');
        Notification::fake();

        $verifiedOwner = User::factory()->create(['role' => 'learner']);
        $pendingOwner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);

        $verified = $this->connector($verifiedOwner, 'verified', Connector::VERIFICATION_VERIFIED);
        $pending = $this->connector($pendingOwner, 'pending', Connector::VERIFICATION_UNDER_REVIEW);

        $verifiedDocument = $this->document($verified, 'due-verified', 'Due verified evidence', Carbon::parse('2026-10-07'), [
            'expires' => true,
        ]);
        $pendingDocument = $this->document($pending, 'due-pending', 'Due pending evidence', Carbon::parse('2026-10-07'), [
            'expires' => true,
        ]);

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();

        $this->assertSame(ConnectorVerificationDocument::STATUS_EXPIRED, $verifiedDocument->fresh()->status);
        $this->assertSame(ConnectorVerificationDocument::STATUS_EXPIRED, $pendingDocument->fresh()->status);
        $this->assertSame(ConnectorVerificationRequirement::STATUS_EXPIRED, $verifiedDocument->requirement()->firstOrFail()->fresh()->status);
        $this->assertSame(ConnectorVerificationRequirement::STATUS_EXPIRED, $pendingDocument->requirement()->firstOrFail()->fresh()->status);

        $this->assertSame('verified', $verified->fresh()->status);
        $this->assertSame(Connector::VERIFICATION_REVERIFICATION_REQUIRED, $verified->fresh()->verification_status);
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame(Connector::VERIFICATION_RESUBMISSION_REQUIRED, $pending->fresh()->verification_status);

        $this->assertDatabaseHas('connector_verification_audits', [
            'connector_id' => $verified->id,
            'connector_verification_document_id' => $verifiedDocument->id,
            'action' => 'document_expired',
        ]);
        $this->assertDatabaseHas('connector_verification_audits', [
            'connector_id' => $verified->id,
            'action' => 'connector_reverification_required',
        ]);
        $this->assertDatabaseHas('connector_verification_audits', [
            'connector_id' => $pending->id,
            'action' => 'connector_resubmission_required',
        ]);

        $this->assertSame(1, Notification::sent($verifiedOwner, ConnectorVerificationActionRequiredNotification::class)->count());
        $this->assertSame(1, Notification::sent($pendingOwner, ConnectorVerificationActionRequiredNotification::class)->count());
        $this->assertSame(2, Notification::sent($admin, ConnectorVerificationActionRequiredNotification::class)->count());

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();

        $this->assertSame(1, $verified->verificationAudits()->where('action', 'document_expired')->count());
        $this->assertSame(1, $pending->verificationAudits()->where('action', 'document_expired')->count());
        $this->assertSame(1, Notification::sent($verifiedOwner, ConnectorVerificationActionRequiredNotification::class)->count());
        $this->assertSame(1, Notification::sent($pendingOwner, ConnectorVerificationActionRequiredNotification::class)->count());
        $this->assertSame(2, Notification::sent($admin, ConnectorVerificationActionRequiredNotification::class)->count());
    }

    public function test_alternative_accepted_evidence_and_foundational_evidence_remain_operational(): void
    {
        Carbon::setTestNow('2026-10-07 01:00:00');
        Notification::fake();

        $owner = User::factory()->create(['role' => 'learner']);
        $connector = $this->connector($owner, 'verified', Connector::VERIFICATION_VERIFIED);

        $expiring = $this->document($connector, 'alternative-expiring', 'Alternative expiring evidence', Carbon::parse('2026-10-07'), [
            'expires' => true,
            'required' => false,
            'rule_group' => 'alternative_identity',
            'rule_mode' => 'any',
        ]);
        $alternative = $this->document($connector, 'alternative-current', 'Alternative current evidence', null, [
            'expires' => false,
            'required' => false,
            'rule_group' => 'alternative_identity',
            'rule_mode' => 'any',
        ]);
        $foundational = $this->document($connector, 'foundational', 'Foundational evidence', null, [
            'expires' => false,
            'required' => true,
            'rule_group' => 'organization_identity',
            'rule_mode' => 'all',
        ]);

        $this->artisan('connectors:expire-verification-documents')->assertSuccessful();

        $this->assertSame(ConnectorVerificationDocument::STATUS_EXPIRED, $expiring->fresh()->status);
        $this->assertSame(ConnectorVerificationDocument::STATUS_ACCEPTED, $alternative->fresh()->status);
        $this->assertSame(ConnectorVerificationDocument::STATUS_ACCEPTED, $foundational->fresh()->status);
        $this->assertSame(ConnectorVerificationRequirement::STATUS_SATISFIED, $alternative->requirement()->firstOrFail()->fresh()->status);
        $this->assertSame(Connector::VERIFICATION_VERIFIED, $connector->fresh()->verification_status);
        Notification::assertNothingSent();
    }

    private function connector(User $owner, string $status, string $verificationStatus): Connector
    {
        return Connector::create([
            'name' => 'Expiration Connector '.str()->random(6),
            'slug' => 'expiration-'.str()->random(8),
            'category' => 'ngo',
            'organization_type' => 'ngo_nonprofit_foundation',
            'contact_number' => '09171234567',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'address_line' => '123 Community Road',
            'status' => $status,
            'verification_status' => $verificationStatus,
            'current_submission_round' => 1,
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);
    }

    private function document(
        Connector $connector,
        string $key,
        string $label,
        ?Carbon $expiryDate,
        array $overrides = [],
    ): ConnectorVerificationDocument {
        $requirementDefaults = [
            'requirement_key' => $key,
            'evidence_category' => 'organization_identity',
            'label' => $label,
            'guidance' => 'Provide evidence.',
            'is_required' => true,
            'rule_group' => 'organization_identity',
            'rule_mode' => 'all',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 1,
            'definition_snapshot' => [
                'key' => $key,
                'label' => $label,
                'required' => true,
                'rule_group' => 'organization_identity',
                'rule_mode' => 'all',
                'minimum_evidence_count' => 1,
                'maximum_evidence_count' => 1,
                'manual_not_applicable_allowed' => false,
                'expires' => false,
            ],
            'status' => ConnectorVerificationRequirement::STATUS_SATISFIED,
        ];
        $requirementAttributes = array_merge($requirementDefaults, [
            'is_required' => $overrides['required'] ?? $requirementDefaults['is_required'],
            'rule_group' => $overrides['rule_group'] ?? $requirementDefaults['rule_group'],
            'rule_mode' => $overrides['rule_mode'] ?? $requirementDefaults['rule_mode'],
        ]);
        $requirementAttributes['definition_snapshot'] = array_merge(
            $requirementAttributes['definition_snapshot'],
            $overrides,
            [
                'key' => $key,
                'label' => $label,
                'required' => $requirementAttributes['is_required'],
                'rule_group' => $requirementAttributes['rule_group'],
                'rule_mode' => $requirementAttributes['rule_mode'],
                'expires' => $overrides['expires'] ?? false,
            ],
        );

        $requirement = $connector->verificationRequirements()->create($requirementAttributes);

        return $requirement->documents()->create([
            'connector_id' => $connector->id,
            'uploaded_by' => $connector->primary_representative_user_id,
            'submission_round' => 1,
            'disk' => 'local',
            'path' => 'connector-verifications/'.$connector->id.'/'.$key.'.pdf',
            'original_name' => $key.'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'content_sha256' => hash('sha256', $key),
            'expiry_date' => $expiryDate,
            'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
        ]);
    }
}
