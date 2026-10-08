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
        $response->assertSee('Regulatory eligibility');
        $response->assertSee('Representative authority');
        $response->assertSee('Completion');
        $response->assertSee('Representative identity');
        $response->assertSee('SEC non-stock registration');
        $response->assertSee('Accepted');
        $response->assertSee('Pending');
        $response->assertSee('Rejected');
        $response->assertSee('Expired');
        $response->assertSee('Superseded');
        $response->assertSee('Submission round');
        $response->assertSee('Approval checklist');
        $response->assertSee('Organization identity established');
        $response->assertSee('Regulatory evidence resolved');
        $response->assertSee('Representative identity verified');
        $response->assertSee('Representative authority established');
        $response->assertSee('Required evidence complete');
        $response->assertSee('Application consistent');
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
