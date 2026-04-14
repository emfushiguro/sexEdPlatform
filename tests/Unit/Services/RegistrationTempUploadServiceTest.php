<?php

namespace Tests\Unit\Services;

use App\Services\Auth\RegistrationTempUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTempUploadServiceTest extends TestCase
{
    public function test_store_saves_metadata_in_session_by_flow_and_step(): void
    {
        Storage::fake('public');

        $service = app(RegistrationTempUploadService::class);
        $metadata = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.jpg', 100, 'image/jpeg'));

        $this->assertSame(
            $metadata,
            session('registration_temp_uploads.parent.government_id')
        );
        $this->assertNotEmpty($metadata['path']);
        Storage::disk('public')->assertExists($metadata['path']);
    }

    public function test_get_returns_stored_metadata_for_rehydration(): void
    {
        Storage::fake('public');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf'));

        $this->assertSame($stored, $service->get('parent', 'government_id'));
    }

    public function test_store_replaces_existing_upload_and_deletes_old_temp_file(): void
    {
        Storage::fake('public');

        $service = app(RegistrationTempUploadService::class);
        $first = $service->store('child', 'verification_document', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'));
        $second = $service->store('child', 'verification_document', UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'));

        $this->assertNotSame($first['path'], $second['path']);
        Storage::disk('public')->assertMissing($first['path']);
        Storage::disk('public')->assertExists($second['path']);
        $this->assertSame($second, $service->get('child', 'verification_document'));
    }

    public function test_remove_deletes_temp_file_and_clears_session_key(): void
    {
        Storage::fake('public');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf'));

        $service->remove('parent', 'government_id');

        Storage::disk('public')->assertMissing($stored['path']);
        $this->assertNull(session('registration_temp_uploads.parent.government_id'));
        $this->assertNull($service->get('parent', 'government_id'));
    }

    public function test_finalize_moves_temp_file_to_target_and_clears_session_key(): void
    {
        Storage::fake('public');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('child', 'verification_document', UploadedFile::fake()->create('birth-cert.pdf', 100, 'application/pdf'));

        $finalPath = $service->finalize('child', 'verification_document', 'child-verifications/55', 'verification-document');

        $this->assertNotNull($finalPath);
        $this->assertStringStartsWith('child-verifications/55/verification-document-', $finalPath);
        $this->assertStringEndsWith('.pdf', $finalPath);
        Storage::disk('public')->assertMissing($stored['path']);
        Storage::disk('public')->assertExists($finalPath);
        $this->assertNull(session('registration_temp_uploads.child.verification_document'));
        $this->assertNull($service->get('child', 'verification_document'));
    }
}
