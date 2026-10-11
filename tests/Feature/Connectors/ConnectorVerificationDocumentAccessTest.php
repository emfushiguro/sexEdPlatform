<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorVerificationDocumentAccessTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_applicant_and_admin_can_stream_current_and_historical_documents_with_private_headers(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()->firstOrFail();
        $document->update([
            'original_name' => '../../unsafe <name>.pdf',
        ]);

        $this->actingAs($owner)
            ->get(route('connector.verification.documents.show', [
                'connector' => $connector,
                'document' => $document,
                'disposition' => 'inline',
            ]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Content-Disposition', 'inline; filename=unsafe_name_.pdf');

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('admin.connectors.verification.documents.show', [
                'connector' => $connector,
                'document' => $document,
                'disposition' => 'attachment',
            ]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=unsafe_name_.pdf');

        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_previewed')->count());
        $this->assertSame(1, $connector->verificationAudits()->where('action', 'document_downloaded')->count());
    }

    public function test_ordinary_members_unrelated_users_and_guests_cannot_stream_evidence(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()->firstOrFail();
        $member = $this->createAdultConnectorMember($connector);

        $this->actingAs($member)
            ->get(route('connector.verification.documents.show', [$connector, $document]))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('connector.verification.documents.show', [$connector, $document]))
            ->assertForbidden();

        $this->get(route('connector.verification.documents.show', [$connector, $document]))
            ->assertForbidden();

        $this->assertSame(0, $connector->verificationAudits()->whereIn('action', [
            'document_previewed',
            'document_downloaded',
        ])->count());
    }

    public function test_document_must_belong_to_connector_and_missing_private_files_are_not_disclosed(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $otherOwner = User::factory()->create();
        $otherConnector = $this->submitInitialApplication($otherOwner, ['name' => 'Other Connector']);
        $document = $connector->verificationDocuments()->firstOrFail();
        $otherDocument = $otherConnector->verificationDocuments()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('connector.verification.documents.show', [
                'connector' => $connector,
                'document' => $otherDocument,
            ]))
            ->assertNotFound();

        Storage::disk('local')->delete($document->path);

        $this->actingAs($owner)
            ->get(route('connector.verification.documents.show', [
                'connector' => $connector,
                'document' => $document,
            ]))
            ->assertNotFound()
            ->assertDontSee($document->path);

        $this->assertSame(0, $connector->verificationAudits()->where('action', 'document_previewed')->count());
    }

    public function test_historical_documents_remain_streamable_and_private_storage_has_no_public_bypass(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()->firstOrFail();
        $document->update([
            'status' => ConnectorVerificationDocument::STATUS_SUPERSEDED,
            'superseded_at' => now(),
        ]);

        $this->assertTrue(Storage::disk('local')->exists($document->path));
        $this->assertTrue(Storage::disk('public')->missing($document->path));

        $this->actingAs($owner)
            ->get(route('connector.verification.documents.show', [
                'connector' => $connector,
                'document' => $document,
            ]))
            ->assertOk();

        $this->get('/storage/'.$document->path)->assertNotFound();
    }

    private function submitInitialApplication(User $owner, array $overrides = []): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload(array_merge($overrides, [
                'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            ])))
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }
}
