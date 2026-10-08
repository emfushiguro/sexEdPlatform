<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoCaptionRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Storage::fake('public');
    }

    protected function refreshTestDatabase(): void
    {
        // Shared cc_db_test schema is provisioned outside this test process.
    }

    public function test_local_video_renders_escaped_caption_tracks_and_one_default(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithLocalVideo();
        $englishPath = 'captions/'.$topic->id.'/english.vtt';
        $koreanPath = 'captions/'.$topic->id.'/korean.vtt';
        Storage::disk('public')->put($englishPath, "WEBVTT\n");
        Storage::disk('public')->put($koreanPath, "WEBVTT\n");
        $topic->captions()->createMany([
            [
                'file_path' => $englishPath,
                'language_code' => 'en',
                'label' => 'English & Korean',
                'is_default' => false,
            ],
            [
                'file_path' => $koreanPath,
                'language_code' => 'ko',
                'label' => '한국어',
                'is_default' => true,
            ],
        ]);

        $response = $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson));
        $response->assertOk()
            ->assertSee('kind="subtitles"', false)
            ->assertSee('srclang="en"', false)
            ->assertSee('label="English &amp; Korean"', false)
            ->assertSee('srclang="ko"', false)
            ->assertSee('label="한국어"', false);

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'kind="subtitles"'));
        $this->assertSame(1, preg_match_all('/<track[^>]*\bdefault\b/i', $html));
        $this->assertStringContainsString(
            'src="'.Storage::disk('public')->url($englishPath).'"',
            $html,
        );
        $this->assertStringContainsString(
            'src="'.Storage::disk('public')->url($koreanPath).'"',
            $html,
        );
    }

    public function test_local_video_without_captions_renders_no_track_element(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLocalVideo();

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertDontSee('<track', false);
    }

    public function test_external_video_keeps_iframe_and_omits_local_tracks(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'topic' => $topic] = $this->enrolledLearnerWithLocalVideo([
            'video_provider' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
            'video_file_path' => null,
        ]);
        $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/external.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertDontSee('<track', false);
    }

    /** @param array<string, mixed> $topicOverrides */
    private function enrolledLearnerWithLocalVideo(array $topicOverrides = []): array
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');
        $learner->gamification()->create([
            'level' => 1,
            'xp' => 0,
            'score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        $topic = LessonTopic::factory()->create(array_merge([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'order' => 1,
            'is_prerequisite' => false,
            'video_provider' => 'local',
            'video_file_path' => 'videos/local.mp4',
        ], $topicOverrides));
        Storage::disk('public')->put('videos/local.mp4', 'video');
        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        return compact('learner', 'module', 'lesson', 'topic');
    }
}
