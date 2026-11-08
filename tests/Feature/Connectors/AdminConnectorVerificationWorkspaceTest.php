<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminConnectorVerificationWorkspaceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_detail_renders_the_safe_verification_workspace(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);

        $documents = $connector->verificationDocuments()->get();
        $accepted = $documents->firstOrFail();
        $pending = $documents->get(1);
        $rejected = $documents->get(2);

        $accepted->update([
            'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
            'issuer' => 'Securities and Exchange Commission',
            'document_number' => 'DOC-SECRET-123',
            'issue_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);
        $pending->update(['status' => ConnectorVerificationDocument::STATUS_PENDING]);
        $rejected->update([
            'status' => ConnectorVerificationDocument::STATUS_REJECTED,
            'applicant_rejection_reason' => 'Please provide a clearer copy.',
            'internal_review_note' => 'INTERNAL SECRET NOTE',
        ]);

        $expired = $accepted->replicate();
        $expired->original_name = 'expired.pdf';
        $expired->path = 'connector-verifications/expired.pdf';
        $expired->status = ConnectorVerificationDocument::STATUS_EXPIRED;
        $expired->save();

        $superseded = $accepted->replicate();
        $superseded->original_name = 'old.pdf';
        $superseded->path = 'connector-verifications/old.pdf';
        $superseded->status = ConnectorVerificationDocument::STATUS_SUPERSEDED;
        $superseded->superseded_at = now()->subDay();
        $superseded->save();

        $connector->verificationRequirements()
            ->where('requirement_key', 'sec_nonstock_registration')
            ->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        $connector->verificationRequirements()
            ->where('requirement_key', 'representative_identity')
            ->update(['status' => ConnectorVerificationRequirement::STATUS_REJECTED]);
        $connector->verificationRequirements()
            ->where('requirement_key', 'representative_authority')
            ->update(['status' => ConnectorVerificationRequirement::STATUS_PENDING]);

        $response = $this->actingAs($admin)
            ->get(route('admin.connectors.show', $connector))
            ->assertOk();

        $response->assertSee('Organization identity');
        $response->assertDontSee('id="purpose-regulatory_eligibility"', false);
        $response->assertSee('Representative authority');
        $response->assertSee('Submitted documents');
        $response->assertSee('Representative identity');
        $response->assertSee('SEC non-stock registration');
        $response->assertSee('Accepted');
        $response->assertSee('Pending');
        $response->assertSee('Rejected');
        $response->assertSee('Expired');
        $response->assertSee('Superseded');
        $response->assertSee('Submission round');
        $response->assertDontSee('Approval checklist');
        $response->assertDontSee('name="checklist[', false);
        $response->assertSee('data-document-preview', false);
        $response->assertSee('data-review-dialog', false);
        $response->assertSee('data-decision-dialog', false);
        $response->assertSee('aria-label="Download', false);
        $response->assertDontSee('No current evidence file submitted.');
        $response->assertDontSee($connector->verificationRequirements()->first()->guidance);
        $response->assertSee('Applicant reason');
        $response->assertSee('Internal note');
        $response->assertSee('Organization logo');
        $response->assertSee(route('admin.connectors.verification.documents.show', [$connector, $accepted]));
        $response->assertSee(route('admin.connectors.verification.documents.show', [$connector, $expired]));
        $response->assertDontSee($accepted->path);
        $response->assertDontSee($accepted->content_sha256);
        $response->assertDontSee('DOC-SECRET-123');
        $response->assertDontSee('INTERNAL SECRET NOTE');
    }

    public function test_index_counts_and_filters_by_verification_status(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->admin();
        $first = $this->submitInitialApplication(User::factory()->create(), ['name' => 'Under Review Workspace']);
        $second = $this->submitInitialApplication(User::factory()->create(), ['name' => 'Resubmission Workspace']);
        $second->update(['verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED]);

        $response = $this->actingAs($admin)
            ->get(route('admin.connectors.index', ['verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED]))
            ->assertOk()
            ->assertSee('Resubmission Workspace')
            ->assertSee('Resubmission required')
            ->assertSee('Server results: 1');
    }

    public function test_empty_optional_evidence_is_hidden_but_uploaded_alternatives_are_visible(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $payload = $this->connectorPayload();
        $payload['evidence']['other_organization_identity'] = $payload['evidence']['sec_nonstock_registration'];
        unset($payload['evidence']['sec_nonstock_registration']);
        $payload['evidence']['deped_authority'] = [
            'files' => [\Illuminate\Http\UploadedFile::fake()->create('recognition.pdf', 100, 'application/pdf')],
        ];
        $connector = $this->submitInitialApplication(User::factory()->create(), [
            'organization_type' => 'school_educational_institution',
            'evidence' => $payload['evidence'],
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.connectors.show', $connector))
            ->assertOk();
        $this->assertTrue(str_contains($response->getContent(), 'DepEd authority'), 'The uploaded school authority must be visible.');
        $this->assertTrue(str_contains($response->getContent(), 'Required alternative'), 'An uploaded mandatory alternative must not be labelled merely optional.');
        $this->assertFalse(str_contains($response->getContent(), 'CHED authority'), 'The empty CHED alternative must not be rendered.');
        $this->assertFalse(str_contains($response->getContent(), 'TESDA program registration'), 'The empty TESDA alternative must not be rendered.');
    }

    public function test_index_renders_logos_and_representative_photos_with_only_view_actions(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $owner->learnerProfile()->create(['username' => 'representative-'.$owner->id, 'avatar_path' => 'avatars/representative.png']);
        $connector = $this->submitInitialApplication($owner);

        $this->actingAs($this->admin())->get(route('admin.connectors.index'))
            ->assertOk()
            ->assertSee('logo_url')
            ->assertSee('representative_avatar_url')
            ->assertViewHas('connectors', fn ($connectors) => $connectors->first()->primaryRepresentative->learnerProfile->avatar_path === 'avatars/representative.png')
            ->assertSee('View connector')
            ->assertDontSee('Open verification workspace')
            ->assertDontSee('selectedActionUrl')
            ->assertDontSee('approve_url');

        $this->get(route('admin.connectors.show', $connector))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('avatars/representative.png'), false);
    }

    public function test_supplemental_validation_preserves_every_entered_field_in_the_modal(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $payload = $this->connectorPayload();
        $payload['evidence']['other_organization_identity'] = $payload['evidence']['sec_nonstock_registration'];
        unset($payload['evidence']['sec_nonstock_registration']);
        $connector = $this->submitInitialApplication(User::factory()->create(), [
            'organization_type' => 'other',
            'organization_type_detail' => 'Community youth center',
            'evidence' => $payload['evidence'],
        ]);
        $this->actingAs($this->admin())
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.verification.request-evidence', $connector), [
                'submission_round' => 1,
                'review_dialog' => 'supplemental-evidence',
                'label' => 'Specific certificate',
                'guidance' => 'Provide the current certificate.',
                'evidence_category' => 'regulatory_eligibility',
                'minimum_evidence_count' => 2,
                'maximum_evidence_count' => 1,
                'accepted_mime_types' => ['image/png'],
                'expires' => '1',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector))
            ->assertSessionHasErrors('maximum_evidence_count');

        $response = $this->get(route('admin.connectors.show', $connector))->assertOk();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/id="supplemental-min"[^>]*value="2"/', $html);
        $this->assertMatchesRegularExpression('/value="regulatory_eligibility"[^>]*selected/', $html);
        $this->assertMatchesRegularExpression('/name="accepted_mime_types\[\]"[^>]*value="image\/png"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="accepted_mime_types\[\]"[^>]*value="application\/pdf"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="expires"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/data-reopen-dialog="supplemental-evidence"/', $html);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function submitInitialApplication(User $owner, array $overrides = []): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload(array_merge($overrides, [
                'logo' => \Illuminate\Http\UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            ])))
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }
}
