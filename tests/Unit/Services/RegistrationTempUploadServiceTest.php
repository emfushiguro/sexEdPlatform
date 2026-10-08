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
        Storage::fake('local');

        $service = app(RegistrationTempUploadService::class);
        $metadata = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.jpg', 100, 'image/jpeg'));

        $this->assertSame(
            $metadata,
            session('registration_temp_uploads.parent.government_id')
        );
        $this->assertNotEmpty($metadata['path']);
        Storage::disk('local')->assertExists($metadata['path']);
        Storage::disk('public')->assertMissing($metadata['path']);
        $this->assertSame('local', $metadata['disk']);
    }

    public function test_get_returns_stored_metadata_for_rehydration(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf'));

        $this->assertSame($stored, $service->get('parent', 'government_id'));
    }

    public function test_store_replaces_existing_upload_and_deletes_old_temp_file(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = app(RegistrationTempUploadService::class);
        $first = $service->store('child', 'verification_document', UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'));
        $second = $service->store('child', 'verification_document', UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'));

        $this->assertNotSame($first['path'], $second['path']);
        Storage::disk('local')->assertMissing($first['path']);
        Storage::disk('local')->assertExists($second['path']);
        Storage::disk('public')->assertMissing($second['path']);
        $this->assertSame($second, $service->get('child', 'verification_document'));
    }

    public function test_remove_deletes_temp_file_and_clears_session_key(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('parent', 'government_id', UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf'));

        $service->remove('parent', 'government_id');

        Storage::disk('local')->assertMissing($stored['path']);
        $this->assertNull(session('registration_temp_uploads.parent.government_id'));
        $this->assertNull($service->get('parent', 'government_id'));
    }

    public function test_finalize_moves_temp_file_to_target_and_clears_session_key(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $service = app(RegistrationTempUploadService::class);
        $stored = $service->store('child', 'verification_document', UploadedFile::fake()->create('birth-cert.pdf', 100, 'application/pdf'));

        $finalPath = $service->finalize('child', 'verification_document', 'child-verifications/55', 'verification-document');

        $this->assertNotNull($finalPath);
        $this->assertStringStartsWith('child-verifications/55/verification-document-', $finalPath);
        $this->assertStringEndsWith('.pdf', $finalPath);
        Storage::disk('local')->assertMissing($stored['path']);
        Storage::disk('local')->assertExists($finalPath);
        Storage::disk('public')->assertMissing($finalPath);
        $this->assertNull(session('registration_temp_uploads.child.verification_document'));
        $this->assertNull($service->get('child', 'verification_document'));
    }

    public function test_get_moves_legacy_public_upload_to_local_before_removing_original(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'registration-temp/parent/government_id/legacy-id.pdf';
        Storage::disk('public')->put($path, 'legacy-private-content');
        session(['registration_temp_uploads.parent.government_id' => [
            'path' => $path,
            'original_name' => 'identity.pdf',
            'mime_type' => 'application/pdf',
            'size' => 23,
            'disk' => 'public',
        ]]);

        $metadata = app(RegistrationTempUploadService::class)->get('parent', 'government_id');

        $this->assertSame('local', $metadata['disk']);
        $this->assertSame('legacy-private-content', Storage::disk('local')->get($path));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_get_keeps_legacy_metadata_when_public_delete_fails(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'registration-temp/parent/government_id/legacy-id.pdf';
        $contents = 'legacy-private-content';
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');
        $publicDisk->put($path, $contents);
        $metadata = [
            'path' => $path,
            'original_name' => 'identity.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'disk' => 'public',
        ];
        session(['registration_temp_uploads.parent.government_id' => $metadata]);
        $this->mockPublicDiskDeleteFailure($path, $publicDisk, $localDisk);

        $exception = null;
        try {
            app(RegistrationTempUploadService::class)->get('parent', 'government_id');
        } catch (\RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame($metadata, session('registration_temp_uploads.parent.government_id'));
        $this->assertSame($contents, $localDisk->get($path));
        $this->assertSame($contents, $publicDisk->get($path));
    }

    public function test_store_does_not_replace_legacy_metadata_when_public_delete_fails(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'registration-temp/parent/government_id/legacy-id.pdf';
        $contents = 'legacy-private-content';
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');
        $publicDisk->put($path, $contents);
        $metadata = [
            'path' => $path,
            'original_name' => 'identity.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'disk' => 'public',
        ];
        session(['registration_temp_uploads.parent.government_id' => $metadata]);
        $this->mockPublicDiskDeleteFailure($path, $publicDisk, $localDisk);

        $exception = null;
        try {
            app(RegistrationTempUploadService::class)->store(
                'parent',
                'government_id',
                UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf')
            );
        } catch (\RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame($metadata, session('registration_temp_uploads.parent.government_id'));
        $this->assertSame($contents, $localDisk->get($path));
        $this->assertSame($contents, $publicDisk->get($path));
    }

    public function test_finalize_does_not_clear_legacy_metadata_when_public_delete_fails(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $path = 'registration-temp/child/verification_document/legacy-proof.pdf';
        $contents = 'legacy-child-evidence';
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');
        $publicDisk->put($path, $contents);
        $metadata = [
            'path' => $path,
            'original_name' => 'proof.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'disk' => 'public',
        ];
        session(['registration_temp_uploads.child.verification_document' => $metadata]);
        $this->mockPublicDiskDeleteFailure($path, $publicDisk, $localDisk);

        $exception = null;
        try {
            app(RegistrationTempUploadService::class)->finalize(
                'child',
                'verification_document',
                'child-verifications/55',
                'verification-document'
            );
        } catch (\RuntimeException $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame($metadata, session('registration_temp_uploads.child.verification_document'));
        $this->assertSame($contents, $localDisk->get($path));
        $this->assertSame($contents, $publicDisk->get($path));
        $this->assertSame([], $localDisk->files('child-verifications/55'));
    }

    public function test_finalize_clears_session_metadata_when_private_temp_file_is_missing(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        session(['registration_temp_uploads.child.verification_document' => [
            'path' => 'registration-temp/child/verification_document/missing.pdf',
            'original_name' => 'missing.pdf',
            'mime_type' => 'application/pdf',
            'size' => 0,
            'disk' => 'local',
        ]]);

        $finalPath = app(RegistrationTempUploadService::class)->finalize(
            'child',
            'verification_document',
            'child-verifications/55',
            'verification-document'
        );

        $this->assertNull($finalPath);
        $this->assertNull(session('registration_temp_uploads.child.verification_document'));
    }

    private function mockPublicDiskDeleteFailure(string $path, object $publicDisk, object $localDisk): void
    {
        $failingPublicDisk = new class($publicDisk, $path) {
            public function __construct(private object $disk, private string $failingPath)
            {
            }

            public function delete(string $path): bool
            {
                return $path === $this->failingPath ? false : $this->disk->delete($path);
            }

            public function __call(string $method, array $arguments): mixed
            {
                return $this->disk->{$method}(...$arguments);
            }
        };

        Storage::shouldReceive('disk')->with('public')->andReturn($failingPublicDisk);
        Storage::shouldReceive('disk')->with('local')->andReturn($localDisk);
    }
}
