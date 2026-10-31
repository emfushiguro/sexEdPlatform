<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorApplicationDocumentStorageTest extends TestCase
{
    public function test_new_documents_use_unique_private_paths_and_store_metadata(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $storage = app(InstructorApplicationDocumentStorage::class);

        $first = $storage->store($user, UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'), 'government_id');
        $second = $storage->store($user, UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'), 'government_id');

        $this->assertNotSame($first['path'], $second['path']);
        $this->assertSame('local', $first['disk']);
        $this->assertSame('government_id_path', $first['column']);
        $this->assertSame('id.pdf', $first['original_name']);
        Storage::disk('local')->assertExists($first['path']);
        Storage::disk('local')->assertExists($second['path']);
    }

    public function test_legacy_rows_keep_their_public_disk_until_migrated(): void
    {
        $storage = app(InstructorApplicationDocumentStorage::class);
        $application = new \App\Models\InstructorApplication([
            'document_disk' => 'public',
            'application_metadata' => [],
        ]);

        $this->assertSame('public', $storage->diskFor($application, 'government_id'));
    }
}
