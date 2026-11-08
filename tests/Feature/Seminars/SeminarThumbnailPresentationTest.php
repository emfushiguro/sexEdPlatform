<?php

namespace Tests\Feature\Seminars;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarThumbnailPresentationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_learner_and_instructor_browse_and_details_use_event_thumbnail(): void
    {
        Storage::fake('public');
        $learner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($this->createCompletedLearner());
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(['purpose' => null]),
            'status' => 'published',
            'schedule' => now()->addDays(3),
            'thumbnail_path' => 'seminars/thumbnails/learner-cover.png',
        ]);
        $url = $seminar->thumbnailUrl();
        $this->actingAs($learner)->get(route('learner.seminars.index'))
            ->assertOk()->assertSee($url, false)->assertSee($seminar->description);
        $this->get(route('learner.seminars.show', $seminar))->assertOk()->assertSee($url, false);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $speaker = $seminar->speakers()->create(['user_id' => $instructor->id, 'display_name' => $instructor->name, 'role' => 'speaker', 'status' => 'accepted']);
        $this->actingAs($instructor)->get(route('instructor.seminars.index'))->assertOk()->assertSee($url, false);
        $this->get(route('instructor.seminars.show', $seminar))->assertOk()->assertSee($url, false);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee($url, false);
        $this->get(route('instructor.speaker-invitations.index'))->assertOk()->assertSee($url, false);
    }

    public function test_connector_and_review_pages_render_uploaded_cover_and_existing_event_fallback(): void
    {
        Storage::fake('public');
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(),
            'status' => 'draft',
            'schedule' => now()->addDays(3),
        ]);

        $this->actingAs($owner)->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()->assertSee('Event image unavailable')->assertSee('data-seminar-thumbnail', false);

        Storage::disk('public')->put('seminars/thumbnails/cover.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII='));
        $seminar->update(['thumbnail_path' => 'seminars/thumbnails/cover.png']);
        $url = $seminar->thumbnailUrl();
        $this->actingAs($owner)->get(route('connector.seminars.edit', [$connector, $seminar]))
            ->assertOk()->assertSee('multipart/form-data')->assertSee('name="thumbnail"', false)
            ->assertSee('Remove saved thumbnail')->assertSee('Objectives (optional)')->assertSee('Your organization decides how many participants to accommodate.');
        $this->get(route('connector.seminars.index', $connector))
            ->assertOk()->assertSee($url, false);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin)->get(route('admin.seminars.show', $seminar))
            ->assertOk()->assertSee($url, false)->assertSee($seminar->title.' event cover');
    }
}
