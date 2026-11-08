<?php

namespace Tests\Feature\Seminars;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarThumbnailTest extends TestCase
{
    use ConnectorTestHelpers;

    private function host(): array
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return [$owner, $this->createVerifiedConnector($owner)];
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('session.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function createEvent($owner, $connector, array $overrides = []): Seminar
    {
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $this->educationalEventPayload($overrides))
            ->assertSessionHasNoErrors()->assertRedirect();

        return $connector->seminars()->latest('id')->firstOrFail();
    }

    public function test_optional_thumbnail_and_objectives_allow_review_and_publication(): void
    {
        [$owner, $connector] = $this->host();
        $payload = $this->educationalEventPayload(['capacity' => '']);
        unset($payload['purpose']);
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $seminar = $connector->seminars()->firstOrFail();
        $this->assertNull($seminar->thumbnail_path);
        $this->assertNull($seminar->thumbnailUrl());
        $this->assertNull($seminar->purpose);
        $this->assertNull($seminar->capacity);
        $this->actingAs($owner)->post(route('connector.seminars.submit-review', [$connector, $seminar]))->assertRedirect();
        $this->assertSame('pending_review', $seminar->fresh()->status);
        $seminar->update(['status' => 'approved']);
        $this->actingAs($owner)->post(route('connector.seminars.publish', [$connector, $seminar]))->assertRedirect();
        $this->assertSame('published', $seminar->fresh()->status);
    }

    public function test_upload_preserve_replace_and_remove_thumbnail(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail' => $this->image()]);
        $original = $seminar->thumbnail_path;
        $this->assertStringStartsWith('seminars/thumbnails/', $original);
        Storage::disk('public')->assertExists($original);
        $this->assertSame(Storage::disk('public')->url($original), $seminar->thumbnailUrl());
        $route = route('connector.seminars.update', [$connector, $seminar]);
        $this->put($route, $this->educationalEventPayload(['purpose' => '', 'title' => 'Updated title']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($original, $seminar->fresh()->thumbnail_path);
        $this->assertNull($seminar->fresh()->purpose);
        $this->put($route, $this->educationalEventPayload(['thumbnail' => $this->image()]))->assertSessionHasNoErrors()->assertRedirect();
        $replacement = $seminar->fresh()->thumbnail_path;
        $this->assertNotSame($original, $replacement);
        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertExists($replacement);
        $this->put($route, $this->educationalEventPayload(['remove_thumbnail' => '1']))->assertRedirect();
        $this->assertNull($seminar->fresh()->thumbnail_path);
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_rejects_invalid_or_oversized_images_and_conflicting_removal(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail' => $this->image()]);
        $path = $seminar->thumbnail_path;
        foreach ([
            ['thumbnail' => UploadedFile::fake()->createWithContent('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
            ['thumbnail' => UploadedFile::fake()->createWithContent('broken.png', 'not an image')],
            ['thumbnail' => UploadedFile::fake()->create('animation.gif', 10, 'image/gif')],
            ['thumbnail' => $this->image()->size(2049)],
            ['thumbnail' => $this->image(), 'remove_thumbnail' => '1'],
        ] as $changes) {
            $this->put(route('connector.seminars.update', [$connector, $seminar]), $this->educationalEventPayload($changes))
                ->assertSessionHasErrors('thumbnail');
            $this->assertSame($path, $seminar->fresh()->thumbnail_path);
            Storage::disk('public')->assertExists($path);
            $this->assertCount(1, Storage::disk('public')->allFiles());
        }
    }

    public function test_rejects_unauthorized_uploads_and_ignores_client_storage_paths(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail_path' => 'modules/other.png']);
        $this->assertNull($seminar->thumbnail_path);
        $outsider = User::factory()->create(['role' => 'learner']);
        $outsider->assignRole('learner');
        $this->actingAs($outsider)->put(route('connector.seminars.update', [$connector, $seminar]), $this->educationalEventPayload(['thumbnail' => $this->image()]))->assertForbidden();
        $otherConnector = $this->createVerifiedConnector($outsider);
        $this->put(route('connector.seminars.update', [$otherConnector, $seminar]), $this->educationalEventPayload(['thumbnail' => $this->image()]))->assertNotFound();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_database_save_preserves_old_image_and_cleans_new_upload(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail' => $this->image()]);
        $path = $seminar->thumbnail_path;
        $dispatcher = Seminar::getEventDispatcher();
        Seminar::setEventDispatcher(clone $dispatcher);
        Seminar::saving(function (Seminar $event): void {
            if ($event->title === 'Fail thumbnail write') {
                throw new RuntimeException('Expected thumbnail save failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->put(route('connector.seminars.update', [$connector, $seminar]), $this->educationalEventPayload(['title' => 'Fail thumbnail write', 'thumbnail' => $this->image()]));
            $this->fail('Expected the database save to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Expected thumbnail save failure', $exception->getMessage());
        } finally {
            Seminar::setEventDispatcher($dispatcher);
        }
        $this->assertSame($path, $seminar->fresh()->thumbnail_path);
        $this->assertSame([$path], Storage::disk('public')->allFiles());
    }

    public function test_failed_storage_write_keeps_saved_thumbnail_and_returns_validation_error(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail' => $this->image()->size(2048)]);
        $path = $seminar->thumbnail_path;
        $disk = Storage::disk('public');
        $failedDisk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $failedDisk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($failedDisk);
        $this->put(route('connector.seminars.update', [$connector, $seminar]), $this->educationalEventPayload(['thumbnail' => $this->image()]))
            ->assertSessionHasErrors('thumbnail');
        $this->assertSame($path, $seminar->fresh()->thumbnail_path);
        $this->assertTrue($disk->exists($path));
    }

    public function test_deleting_draft_cleans_image_but_published_event_keeps_it(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector, ['thumbnail' => $this->image()]);
        $path = $seminar->thumbnail_path;
        $seminar->update(['status' => 'published', 'published_at' => now()]);
        $route = route('connector.seminars.destroy', [$connector, $seminar]);
        $this->delete($route)->assertStatus(422);
        Storage::disk('public')->assertExists($path);
        $seminar->update(['status' => 'draft', 'published_at' => null]);
        $this->delete($route)->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertModelMissing($seminar);
    }

    public function test_removal_never_deletes_other_public_media(): void
    {
        [$owner, $connector] = $this->host();
        $seminar = $this->createEvent($owner, $connector);
        Storage::disk('public')->put('modules/keep.png', 'other media');
        $seminar->forceFill(['thumbnail_path' => 'modules/keep.png'])->save();
        $this->put(route('connector.seminars.update', [$connector, $seminar]), $this->educationalEventPayload(['remove_thumbnail' => '1']))->assertRedirect();
        Storage::disk('public')->assertExists('modules/keep.png');
        $this->assertNull($seminar->fresh()->thumbnail_path);
    }

    public function test_capacity_bounds_and_optional_objectives_are_validated(): void
    {
        [$owner, $connector] = $this->host();
        foreach ([1, 100000] as $capacity) {
            $seminar = $this->createEvent($owner, $connector, ['purpose' => '', 'capacity' => $capacity]);
            $this->assertSame($capacity, (int) $seminar->capacity);
            $this->assertNull($seminar->purpose);
        }
        foreach ([0, 100001, 1.5] as $capacity) {
            $this->post(route('connector.seminars.store', $connector), $this->educationalEventPayload(['capacity' => $capacity]))->assertSessionHasErrors('capacity');
        }
        $this->post(route('connector.seminars.store', $connector), $this->educationalEventPayload(['description' => '']))->assertSessionHasErrors('description');
    }
}
