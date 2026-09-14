<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class VideoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_runtime_is_configured_for_the_100_mib_video_boundary(): void
    {
        $this->assertFileExists(public_path('.user.ini'));
        $settings = parse_ini_file(public_path('.user.ini'));

        $this->assertSame('100M', $settings['upload_max_filesize'] ?? null);
        $this->assertSame('110M', $settings['post_max_size'] ?? null);
    }

    public function test_instructor_can_upload_a_video_at_the_100_mib_boundary(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $response = $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Boundary video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'boundary.mp4',
                    102400,
                    'video/mp4',
                ),
            ]);

        $response->assertRedirect(route('instructor.lessons.show', $lesson));
        $this->assertDatabaseHas('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Boundary video',
            'video_provider' => 'local',
        ]);
        $this->assertCount(1, Storage::disk('public')->allFiles('videos'));
    }

    public function test_video_create_storage_failure_returns_ajax_error_without_topic_or_video(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);

        $manager = Mockery::mock(FilesystemFactory::class);
        $manager->shouldReceive('disk')->with('public')->andReturn($disk);
        Storage::swap($manager);

        $response = $this->actingAs($instructor)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Failed video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create('failed.mp4', 100, 'video/mp4'),
            ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.error.0', 'Failed to store video upload.');
        $this->assertDatabaseMissing('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Failed video',
        ]);
    }

    public function test_instructor_cannot_upload_a_video_over_the_100_mib_boundary(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $response = $this->actingAs($instructor)
            ->from(route('instructor.topics.create', ['lesson' => $lesson]))
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Oversized video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'oversized.mp4',
                    102401,
                    'video/mp4',
                ),
            ]);

        $response->assertSessionHasErrors('video_file');
        $this->assertDatabaseMissing('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Oversized video',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles('videos'));
    }

    public function test_video_create_ajax_response_contains_the_lesson_redirect(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $response = $this->actingAs($instructor)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'AJAX video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create('ajax.mp4', 100, 'video/mp4'),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Topic created successfully!')
            ->assertJsonPath('redirect', route('instructor.lessons.show', $lesson));
    }

    public function test_video_update_ajax_response_contains_the_lesson_redirect(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        Storage::disk('public')->put('videos/old.mp4', 'old video');
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'video_provider' => 'local',
            'video_file_path' => 'videos/old.mp4',
        ]);

        $response = $this->actingAs($instructor)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('instructor.topics.update', $topic), [
                'title' => 'Updated video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Topic updated successfully!')
            ->assertJsonPath('redirect', route('instructor.lessons.show', $lesson));
        Storage::disk('public')->assertMissing('videos/old.mp4');
        $this->assertNotSame('videos/old.mp4', $topic->fresh()->video_file_path);
    }

    public function test_video_update_persistence_failure_returns_ajax_error_and_deletes_replacement(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        Storage::disk('public')->put('videos/old.mp4', 'old video');
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'video_provider' => 'local',
            'video_file_path' => 'videos/old.mp4',
        ]);

        LessonTopic::saving(static function (LessonTopic $model): bool {
            return false;
        });

        try {
            $response = $this->actingAs($instructor)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->put(route('instructor.topics.update', $topic), [
                    'title' => 'Vetoed replacement',
                    'type' => 'video',
                    'duration' => 3,
                    'video_source' => 'upload',
                    'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
                ]);
        } finally {
            LessonTopic::flushEventListeners();
        }

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.error.0', 'Failed to update topic.');
        Storage::disk('public')->assertExists('videos/old.mp4');
        $this->assertCount(1, Storage::disk('public')->allFiles('videos'));
        $this->assertSame('videos/old.mp4', $topic->fresh()->video_file_path);
    }

    public function test_failed_replacement_storage_does_not_delete_the_existing_video(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'video_provider' => 'local',
            'video_file_path' => 'videos/old.mp4',
        ]);

        $deleted = false;
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('delete')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () use (&$deleted): bool {
                $deleted = true;

                return true;
            });
        $disk->shouldReceive('putFileAs')
            ->once()
            ->andThrow(new RuntimeException('disk full'));

        $manager = Mockery::mock(FilesystemFactory::class);
        $manager->shouldReceive('disk')->with('public')->andReturn($disk);
        Storage::swap($manager);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($instructor)
                ->put(route('instructor.topics.update', $topic), [
                    'title' => 'Failed replacement',
                    'type' => 'video',
                    'duration' => 3,
                    'video_source' => 'upload',
                    'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
                ]);

            $this->fail('Expected replacement storage to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('disk full', $exception->getMessage());
        }

        $this->assertFalse($deleted);
        $this->assertSame('videos/old.mp4', $topic->fresh()->video_file_path);
    }

    public function test_topic_authoring_pages_render_the_video_upload_progress_contract(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'video_provider' => 'local',
            'video_file_path' => 'videos/current.mp4',
        ]);
        $acceptedVideoFormats = 'accept=".mp4,.mpeg,.mpg,.mov,.avi,.webm,video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm"';

        $responses = [
            $this->actingAs($instructor)
                ->get(route('instructor.topics.create', ['lesson' => $lesson])),
            $this->actingAs($instructor)
                ->get(route('instructor.topics.edit', $topic)),
        ];

        foreach ($responses as $response) {
            $response->assertOk()
                ->assertSee('data-video-upload-form', false)
                ->assertSee('data-video-max-bytes="104857600"', false)
                ->assertSee('data-video-file-input', false)
                ->assertSee('for="video_file"', false)
                ->assertSee('id="video_file"', false)
                ->assertSee($acceptedVideoFormats, false)
                ->assertSee('aria-describedby="videoFileName videoFileClientError"', false)
                ->assertSee('aria-invalid="false"', false)
                ->assertSee('data-video-error', false)
                ->assertSee('data-video-upload-overlay', false)
                ->assertSee('role="dialog"', false)
                ->assertSee('aria-modal="true"', false)
                ->assertSee('tabindex="-1"', false)
                ->assertSee('data-upload-progress', false)
                ->assertSee('role="progressbar"', false)
                ->assertSee('aria-valuemin="0"', false)
                ->assertSee('aria-valuemax="100"', false)
                ->assertSee('aria-valuenow="0"', false)
                ->assertSee('data-upload-status', false)
                ->assertSee('aria-live="polite"', false)
                ->assertSee('data-video-upload-form-error', false);
        }

        $errors = new \Illuminate\Support\ViewErrorBag;
        $errors->put('default', new \Illuminate\Support\MessageBag([
            'video_file' => ['A valid video is required.'],
        ]));

        foreach ([
            $this->actingAs($instructor)
                ->withSession(['errors' => $errors])
                ->get(route('instructor.topics.create', ['lesson' => $lesson])),
            $this->actingAs($instructor)
                ->withSession(['errors' => $errors])
                ->get(route('instructor.topics.edit', $topic)),
        ] as $response) {
            $response->assertOk()
                ->assertSee('aria-describedby="videoFileName videoFileClientError videoFileServerError"', false)
                ->assertSee('aria-invalid="true"', false)
                ->assertSee('id="videoFileServerError"', false)
                ->assertSee('A valid video is required.', false);
        }
    }

    /** @return array{User, Lesson} */
    private function topicAuthoringFixture(): array
    {
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return [$instructor, $lesson];
    }
}
