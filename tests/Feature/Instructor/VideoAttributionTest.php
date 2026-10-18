<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class VideoAttributionTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_owner_can_save_edit_and_clear_credits_without_changing_publication(): void
    {
        [$author, $lesson] = $this->fixture();
        $metadata = $this->credits();
        $this->actingAs($author)->post(route('instructor.topics.store'), $this->payload($lesson) + ['video_attribution' => $metadata])->assertSessionHasNoErrors()->assertRedirect();
        $topic = $lesson->topics()->firstOrFail();
        $this->assertSame($metadata + ['source_url' => 'https://youtu.be/dQw4w9WgXcQ'], $topic->video_attribution);
        $this->assertSame('youtube', $topic->video_provider);
        $this->get(route('instructor.topics.edit', $topic))->assertOk()->assertSee('Original title')->assertSee('https://youtu.be/dQw4w9WgXcQ', false)->assertSee('Video Source &amp; Attribution', false);
        $metadata['creator'] = 'Updated creator';
        $this->put(route('instructor.topics.update', $topic), $this->payload($lesson) + ['video_attribution' => $metadata])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Updated creator', $topic->fresh()->video_attribution['creator']);
        $this->assertFalse($lesson->fresh()->is_published);
        $this->put(route('instructor.topics.update', $topic), $this->payload($lesson) + ['video_attribution' => []])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('creator', $topic->fresh()->video_attribution);
    }

    public function test_credit_fields_are_optional_for_external_and_owned_uploads(): void
    {
        Storage::fake('public');
        [$author, $lesson] = $this->fixture();
        $this->actingAs($author)->post(route('instructor.topics.store'), $this->payload($lesson))->assertSessionHasNoErrors();
        $payload = $this->payload($lesson);
        unset($payload['video_url']);
        $payload['video_source'] = 'upload';
        $payload['video_file'] = UploadedFile::fake()->create('owned.mp4', 1, 'video/mp4');
        $this->post(route('instructor.topics.store'), $payload)->assertSessionHasNoErrors();
        $upload = $lesson->topics()->where('video_provider', 'local')->firstOrFail();
        $this->assertNull($upload->video_attribution);
        $this->assertNotNull($upload->video_file_path);
    }

    public function test_invalid_urls_and_license_values_fail_before_persistence(): void
    {
        [$author, $lesson] = $this->fixture();
        $this->actingAs($author);
        foreach (['javascript:alert(1)', 'ftp://example.org/file.mp4', 'https://example.org/embed', 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'] as $url) {
            $this->postJson(route('instructor.topics.store'), array_replace($this->payload($lesson), ['video_url' => $url]))->assertUnprocessable()->assertJsonValidationErrors('video_url');
        }
        foreach ([
            ['source_url' => 'javascript:alert(1)'],
            ['license' => 'made-up'],
            ['license' => 'other'],
            ['permission_status' => 'verified'],
            ['creator' => str_repeat('a', 256)],
        ] as $metadata) {
            $this->postJson(route('instructor.topics.store'), $this->payload($lesson) + ['video_attribution' => $metadata])->assertUnprocessable();
        }
        $this->assertSame(0, $lesson->topics()->count());
        $this->postJson(route('instructor.topics.store'), $this->payload($lesson) + ['video_attribution' => ['license' => 'other', 'license_name' => 'Publisher educational license']])->assertOk();
    }

    public function test_non_owner_cannot_create_or_update_credits(): void
    {
        [, $lesson] = $this->fixture();
        [$other] = $this->fixture();
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'video']);
        $this->actingAs($other)->post(route('instructor.topics.store'), $this->payload($lesson))->assertForbidden();
        $this->put(route('instructor.topics.update', $topic), $this->payload($lesson) + ['video_attribution' => $this->credits()])->assertForbidden();
        $this->assertNull($topic->fresh()->video_attribution);
    }

    public function test_admin_can_credit_platform_content_but_cannot_mutate_instructor_content(): void
    {
        [$admin, $lesson] = $this->fixture('admin');
        $this->actingAs($admin)->post(route('admin.topics.store'), $this->payload($lesson) + ['video_attribution' => $this->credits()])->assertSessionHasNoErrors()->assertRedirect();
        $topic = $lesson->topics()->firstOrFail();
        $this->put(route('admin.topics.update', $topic), $this->payload($lesson) + ['video_attribution' => ['creator' => 'Platform credit']])->assertSessionHasNoErrors();
        $this->assertSame('Platform credit', $topic->fresh()->video_attribution['creator']);
        [, $otherLesson] = $this->fixture();
        $this->post(route('admin.topics.store'), $this->payload($otherLesson))->assertForbidden();
    }

    public function test_omitted_metadata_is_preserved_and_non_video_metadata_is_cleared(): void
    {
        [$author, $lesson] = $this->fixture();
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'video', 'video_provider' => 'youtube', 'video_id' => 'dQw4w9WgXcQ', 'video_attribution' => $this->credits()]);
        $this->actingAs($author)->put(route('instructor.topics.update', $topic), $this->payload($lesson))->assertSessionHasNoErrors();
        $this->assertSame('Creator channel', $topic->fresh()->video_attribution['creator']);
        $this->put(route('instructor.topics.update', $topic), ['title' => 'Text', 'type' => 'text', 'duration' => 2, 'text_content' => 'Text body'])->assertSessionHasNoErrors();
        $this->assertNull($topic->fresh()->video_attribution);
    }

    public function test_direct_file_url_uses_the_canonical_field_and_rejects_oversized_urls(): void
    {
        [$author, $lesson] = $this->fixture();
        $payload = array_replace($this->payload($lesson), ['video_url' => 'https://media.example.org/original.webm', 'video_attribution' => ['creator' => 'Publisher']]);
        $this->actingAs($author)->post(route('instructor.topics.store'), $payload)->assertSessionHasNoErrors();
        $topic = $lesson->topics()->firstOrFail();
        $this->assertSame($payload['video_url'], $topic->video_id);
        $this->assertSame($payload['video_url'], $topic->video_url);
        $this->assertSame(['creator' => 'Publisher'], $topic->video_attribution);
        $payload['video_url'] = 'https://media.example.org/'.str_repeat('a', 250).'.mp4';
        $this->putJson(route('instructor.topics.update', $topic), $payload)->assertUnprocessable()->assertJsonValidationErrors('video_url');
    }

    public function test_selected_source_wins_over_stale_hidden_inputs(): void
    {
        Storage::fake('public');
        [$author, $lesson] = $this->fixture();
        $payload = $this->payload($lesson) + ['video_file' => UploadedFile::fake()->create('unused.mp4', 1, 'video/mp4')];
        $this->actingAs($author)->post(route('instructor.topics.store'), $payload)->assertSessionHasNoErrors();
        $external = $lesson->topics()->firstOrFail();
        $this->assertSame('youtube', $external->video_provider);
        $this->assertSame([], Storage::disk('public')->allFiles('videos'));
        $local = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'video', 'video_provider' => 'local', 'video_file_path' => 'videos/owned.mp4']);
        $payload = array_replace($this->payload($lesson), ['video_source' => 'upload', 'video_url' => 'https://untrusted.example.org/embed']);
        $this->put(route('instructor.topics.update', $local), $payload)->assertSessionHasNoErrors();
        $this->assertSame('local', $local->fresh()->video_provider);
        $this->assertSame('videos/owned.mp4', $local->fresh()->video_file_path);
    }

    public function test_required_topic_fields_and_malformed_metadata_are_rejected(): void
    {
        [$author, $lesson] = $this->fixture();
        $this->actingAs($author)->postJson(route('instructor.topics.store'), ['lesson_id' => $lesson->id, 'type' => 'video', 'video_source' => 'url'])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'duration', 'video_url']);
        $this->postJson(route('instructor.topics.store'), $this->payload($lesson) + ['video_attribution' => ['source_url' => ['invalid']]])->assertUnprocessable()->assertJsonValidationErrors('video_attribution.source_url');
    }

    private function fixture(string $role = 'instructor'): array
    {
        $author = User::factory()->create(['role' => $role, 'status' => 'active']);
        $author->assignRole($role);
        $module = Module::factory()->create(['created_by' => $author->id, 'content_owner_type' => $role === 'admin' ? 'admin' : 'instructor']);

        return [$author, Lesson::factory()->create(['module_id' => $module->id, 'is_published' => false])];
    }

    private function payload(Lesson $lesson): array
    {
        return ['lesson_id' => $lesson->id, 'title' => 'Lesson title', 'type' => 'video', 'duration' => 3, 'video_source' => 'url', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'];
    }

    private function credits(): array
    {
        return ['original_title' => 'Original title', 'creator' => 'Creator channel', 'license' => 'cc_by', 'attribution_text' => 'Credit supplied by instructor', 'permission_status' => 'pending'];
    }
}
