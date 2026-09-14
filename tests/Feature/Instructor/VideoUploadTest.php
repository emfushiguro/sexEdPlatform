<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
