<?php

declare(strict_types=1);

namespace Tests\Feature\Instructor;

use App\Models\InteractiveActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InteractiveActivityImageLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_and_listing_return_the_scoped_storage_path_and_accept_webp(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $upload = $this->actingAs($instructor)->postJson(route('instructor.image-library.upload'), [
            'image' => UploadedFile::fake()->create('diagram.webp', 10, 'image/webp'),
        ])->assertOk();

        $path = $upload->json('path');
        $this->assertIsString($path);
        $this->assertStringStartsWith("quiz-images/user-{$instructor->id}/", $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($instructor)
            ->getJson(route('instructor.image-library.json'))
            ->assertOk()
            ->assertJsonFragment(['path' => $path]);
    }

    public function test_referenced_activity_image_cannot_be_deleted_from_the_library(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $path = "quiz-images/user-{$instructor->id}/used.png";
        Storage::disk('public')->put($path, 'image-bytes');
        InteractiveActivity::factory()->create(['configuration' => [
            'schema_version' => 1,
            'pairs' => [
                ['id' => 'pair-1', 'left' => ['id' => 'left-1', 'kind' => 'text', 'value' => '', 'image_path' => $path, 'image_alt' => 'Used diagram'], 'right' => ['id' => 'right-1', 'kind' => 'text', 'value' => 'One', 'image_path' => null, 'image_alt' => null]],
                ['id' => 'pair-2', 'left' => ['id' => 'left-2', 'kind' => 'text', 'value' => 'Two', 'image_path' => null, 'image_alt' => null], 'right' => ['id' => 'right-2', 'kind' => 'text', 'value' => 'Second', 'image_path' => null, 'image_alt' => null]],
            ],
        ]]);

        $this->actingAs($instructor)
            ->from(route('instructor.image-library.index'))
            ->delete(route('instructor.image-library.delete', basename($path)))
            ->assertRedirect(route('instructor.image-library.index'))
            ->assertSessionHas('error', 'This image is used by an interactive activity and cannot be deleted.');

        Storage::disk('public')->assertExists($path);
    }

    public function test_unreferenced_library_image_retains_existing_delete_behavior(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $path = "quiz-images/user-{$instructor->id}/unused.png";
        Storage::disk('public')->put($path, 'image-bytes');

        $this->actingAs($instructor)
            ->delete(route('instructor.image-library.delete', basename($path)))
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing($path);
    }
}
