<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorImageLibraryThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_library_renders_gallery_metadata_and_action_control_markers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('quiz-images/sample-one.jpg', 'fake-image-content');
        Storage::disk('public')->put('quiz-images/sample-two.png', 'fake-image-content');

        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('instructor.image-library.index'))
            ->assertOk()
            ->assertSee('id="image-gallery-grid"', false)
            ->assertSee('id="image-metadata-drawer"', false)
            ->assertSee('data-image-action-controls', false);
    }
}
