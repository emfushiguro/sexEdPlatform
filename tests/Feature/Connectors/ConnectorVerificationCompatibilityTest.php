<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorVerificationCompatibilityTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_verified_and_legacy_verified_connectors_show_the_platform_claim_and_optional_logo(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $verified = $this->publicConnector($owner, [
            'name' => 'Current Verified Connector',
            'verification_status' => Connector::VERIFICATION_VERIFIED,
            'logo_path' => 'connector-logos/current/logo.png',
        ]);
        Storage::disk('public')->put($verified->logo_path, 'logo');

        $response = $this->actingAs($owner)->get(route('connectors.show', $verified));

        $explanation = 'Verified by Conscious Connections based on submitted organizational and authorization documents.';
        $response->assertOk()
            ->assertSee($explanation)
            ->assertSee('alt="'.$verified->name.' logo"', false)
            ->assertSee('connector-logos/current/logo.png', false);
        $this->assertSame(1, substr_count($response->getContent(), $explanation));

        $legacy = $this->publicConnector($owner, [
            'name' => 'Legacy Verified Connector',
            'verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED,
        ]);

        $legacyResponse = $this->actingAs($owner)->get(route('connectors.show', $legacy));

        $legacyResponse->assertOk()
            ->assertSee($explanation)
            ->assertSee('Legacy Verified Connector');
    }

    public function test_reverification_is_neutral_and_public_html_excludes_private_evidence(): void
    {
        $owner = User::factory()->create();
        $connector = $this->publicConnector($owner, [
            'name' => 'Reverification Connector',
            'verification_status' => Connector::VERIFICATION_REVERIFICATION_REQUIRED,
        ]);
        $requirement = $connector->verificationRequirements()->create([
            'requirement_key' => 'private_identity',
            'evidence_category' => 'representative_identity',
            'label' => 'Representative identity',
            'guidance' => 'Private evidence.',
            'is_required' => true,
            'rule_group' => 'representative_identity',
            'rule_mode' => 'all',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 1,
            'definition_snapshot' => ['expires' => true],
            'status' => ConnectorVerificationRequirement::STATUS_EXPIRED,
        ]);
        $document = $requirement->documents()->create([
            'connector_id' => $connector->id,
            'uploaded_by' => $owner->id,
            'submission_round' => 1,
            'disk' => 'local',
            'path' => 'connector-verifications/private/secret.pdf',
            'original_name' => 'secret.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'content_sha256' => str_repeat('a', 64),
            'document_number' => 'DOC-PRIVATE-123',
            'expiry_date' => Carbon::parse('2026-12-31'),
            'status' => ConnectorVerificationDocument::STATUS_EXPIRED,
        ]);
        $connector->verificationAudits()->create([
            'connector_verification_requirement_id' => $requirement->id,
            'connector_verification_document_id' => $document->id,
            'action' => 'document_expired',
            'internal_notes' => 'Private moderation note.',
            'new_state' => ['status' => ConnectorVerificationDocument::STATUS_EXPIRED],
            'submission_round' => 1,
        ]);

        $response = $this->actingAs($owner)->get(route('connectors.show', $connector));

        $response->assertOk()
            ->assertSee('Verification update in progress')
            ->assertDontSee('Verified by Conscious Connections based on submitted organizational and authorization documents.')
            ->assertDontSee('Representative identity')
            ->assertDontSee('secret.pdf')
            ->assertDontSee('DOC-PRIVATE-123')
            ->assertDontSee('2026-12-31')
            ->assertDontSee('Private moderation note.')
            ->assertDontSee(route('connector.verification.documents.show', [$connector, $document]), false);
    }

    public function test_non_verified_operational_statuses_do_not_render_public_verification_claims(): void
    {
        $owner = User::factory()->create();
        $explanation = 'Verified by Conscious Connections based on submitted organizational and authorization documents.';

        foreach (['pending', 'rejected', 'suspended', 'withdrawn'] as $status) {
            $connector = $this->publicConnector($owner, [
                'name' => ucfirst($status).' Connector',
                'status' => $status,
                'verification_status' => $status === 'rejected'
                    ? Connector::VERIFICATION_REJECTED
                    : Connector::VERIFICATION_UNDER_REVIEW,
            ]);

            $response = $this->actingAs($owner)->get(route('connectors.show', $connector));

            $response->assertNotFound();
            $this->assertStringNotContainsString($explanation, $response->getContent());
        }
    }

    public function test_discovery_distinguishes_verified_and_reverification_states(): void
    {
        $viewer = User::factory()->create();
        $creator = User::factory()->create();
        $verified = $this->publicConnector($creator, [
            'name' => 'Discovery Verified',
            'verification_status' => Connector::VERIFICATION_VERIFIED,
        ]);
        $this->publicConnector($creator, [
            'name' => 'Discovery Reverification',
            'verification_status' => Connector::VERIFICATION_REVERIFICATION_REQUIRED,
        ]);
        $this->publicConnector($creator, [
            'name' => 'Discovery Pending',
            'status' => 'pending',
            'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
        ]);

        $response = $this->actingAs($viewer)->get(route('connectors.index'));

        $response->assertOk()
            ->assertSee($verified->name)
            ->assertSee('Verification update in progress')
            ->assertSee('Verified by Conscious Connections based on submitted organizational and authorization documents.')
            ->assertDontSee('Discovery Pending');
    }

    private function publicConnector(User $creator, array $overrides = []): Connector
    {
        return Connector::create(array_merge([
            'name' => 'Public Connector '.str()->random(6),
            'slug' => 'public-'.str()->random(8),
            'category' => 'ngo',
            'organization_type' => 'ngo_nonprofit_foundation',
            'contact_number' => '09171234567',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'address_line' => '123 Community Road',
            'status' => 'verified',
            'verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED,
            'created_by' => $creator->id,
            'primary_representative_user_id' => $creator->id,
        ], $overrides));
    }
}
