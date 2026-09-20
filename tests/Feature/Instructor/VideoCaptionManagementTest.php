<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\LessonTopicCaption;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VideoCaptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_owner_can_create_multiple_tracks_with_one_default(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Captioned video',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [
                0 => ['file' => $this->vtt('english.vtt', 'Hello'), 'language_code' => 'EN', 'label' => 'English'],
                1 => ['file' => $this->vtt('filipino.vtt', 'Kumusta'), 'language_code' => 'fil', 'label' => 'Filipino'],
            ],
            'caption_default' => 1,
        ])->assertRedirect();

        $topic = LessonTopic::where('title', 'Captioned video')->firstOrFail();
        $this->assertDatabaseHas('lesson_topic_captions', [
            'lesson_topic_id' => $topic->id,
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('lesson_topic_captions', [
            'lesson_topic_id' => $topic->id,
            'language_code' => 'fil',
            'label' => 'Filipino',
            'is_default' => true,
        ]);
        $this->assertCount(2, Storage::disk('public')->allFiles('captions/'.$topic->id));
    }

    public function test_duplicate_languages_and_external_caption_uploads_are_rejected(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Duplicates',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [
                ['file' => $this->vtt('one.vtt'), 'language_code' => 'en', 'label' => 'English'],
                ['file' => $this->vtt('two.vtt'), 'language_code' => 'EN', 'label' => 'English alternate'],
            ],
        ])->assertSessionHasErrors('captions.1.language_code');

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'External',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'url',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'captions' => [
                ['file' => $this->vtt('external.vtt'), 'language_code' => 'en', 'label' => 'English'],
            ],
        ])->assertSessionHasErrors('captions');
    }

    public function test_owner_can_replace_remove_and_change_the_default_track(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $english = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/english-old.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        $korean = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/korean-old.vtt',
            'language_code' => 'ko',
            'label' => 'Korean',
            'is_default' => false,
        ]);
        Storage::disk('public')->put($english->file_path, $this->vttText());
        Storage::disk('public')->put($korean->file_path, $this->vttText());

        $this->actingAs($instructor)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [
                0 => ['id' => $english->id, 'remove' => 1],
                1 => [
                    'id' => $korean->id,
                    'file' => $this->vtt('korean-new.vtt', 'Annyeong'),
                    'language_code' => 'ko',
                    'label' => '한국어',
                ],
            ],
            'caption_default' => 1,
        ])->assertRedirect();

        $this->assertDatabaseMissing('lesson_topic_captions', ['id' => $english->id]);
        $updated = $korean->fresh();
        $this->assertTrue($updated->is_default);
        $this->assertNotSame('captions/'.$topic->id.'/korean-old.vtt', $updated->file_path);
        Storage::disk('public')->assertMissing($english->file_path);
        Storage::disk('public')->assertMissing('captions/'.$topic->id.'/korean-old.vtt');
        Storage::disk('public')->assertExists($updated->file_path);
    }

    public function test_switching_provider_and_deleting_topic_clean_caption_files(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $caption = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/english.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        Storage::disk('public')->put($caption->file_path, $this->vttText());

        $this->actingAs($instructor)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'url',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertRedirect();

        $this->assertDatabaseMissing('lesson_topic_captions', ['id' => $caption->id]);
        Storage::disk('public')->assertMissing($caption->file_path);

        $local = $this->localVideoTopic($lesson);
        $path = 'captions/'.$local->id.'/delete.vtt';
        $local->captions()->create([
            'file_path' => $path,
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        Storage::disk('public')->put($path, $this->vttText());

        $this->actingAs($instructor)
            ->delete(route('instructor.topics.destroy', $local))
            ->assertRedirect();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_another_instructor_cannot_manage_caption_tracks(): void
    {
        [$owner, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $other = User::factory()->create();
        $other->assignRole('instructor');

        $this->actingAs($other)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [
                ['file' => $this->vtt('attack.vtt'), 'language_code' => 'en', 'label' => 'English'],
            ],
        ])->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles('captions'));
    }

    public function test_caption_validation_rejects_invalid_and_oversized_webvtt(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $base = [
            'lesson_id' => $lesson->id,
            'title' => 'Invalid captions',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
        ];

        $this->actingAs($instructor)->post(route('instructor.topics.store'), $base + [
            'video_file' => $this->video(),
            'captions' => [[
                'file' => UploadedFile::fake()->createWithContent('invalid.vtt', 'not webvtt'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.file');

        $this->actingAs($instructor)->post(route('instructor.topics.store'), $base + [
            'video_file' => $this->video(),
            'captions' => [[
                'file' => UploadedFile::fake()->create('oversized.vtt', 2049, 'text/plain'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.file');
    }

    public function test_captions_may_be_saved_without_a_default(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Optional default',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [[
                'file' => $this->vtt('english.vtt'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('lesson_topic_captions', [
            'language_code' => 'en',
            'is_default' => false,
        ]);
    }

    public function test_caption_ids_cannot_be_moved_between_topics(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $target = $this->localVideoTopic($lesson);
        $other = $this->localVideoTopic($lesson);
        $foreignCaption = $other->captions()->create([
            'file_path' => 'captions/'.$other->id.'/foreign.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => false,
        ]);

        $this->actingAs($instructor)->put(route('instructor.topics.update', $target), [
            'title' => $target->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [[
                'id' => $foreignCaption->id,
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.id');
    }

    public function test_failed_caption_persistence_removes_new_files(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        LessonTopicCaption::creating(static function (): void {
            throw new RuntimeException('caption persistence failed');
        });

        try {
            $this->actingAs($instructor)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post(route('instructor.topics.store'), [
                    'lesson_id' => $lesson->id,
                    'title' => 'Failed caption',
                    'type' => 'video',
                    'duration' => 3,
                    'video_source' => 'upload',
                    'video_file' => $this->video(),
                    'captions' => [[
                        'file' => $this->vtt('english.vtt'),
                        'language_code' => 'en',
                        'label' => 'English',
                    ]],
                ])
                ->assertStatus(500);
        } finally {
            LessonTopicCaption::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('public')->allFiles('captions'));
        $this->assertDatabaseMissing('lesson_topics', ['title' => 'Failed caption']);
    }

    private function vtt(string $name, string $cue = 'Hello'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->vttText($cue));
    }

    private function vttText(string $cue = 'Hello'): string
    {
        return "WEBVTT\n\n00:00.000 --> 00:02.000\n".$cue."\n";
    }

    private function video(): UploadedFile
    {
        return UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
    }

    private function localVideoTopic(Lesson $lesson): LessonTopic
    {
        Storage::disk('public')->put('videos/current.mp4', 'video');

        return LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'duration' => 3,
            'video_provider' => 'local',
            'video_file_path' => 'videos/current.mp4',
        ]);
    }

    /** @return array{User, Lesson} */
    private function topicAuthoringFixture(): array
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return [$instructor, $lesson];
    }
}
