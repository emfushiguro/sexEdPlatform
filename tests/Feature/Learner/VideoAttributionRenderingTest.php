<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class VideoAttributionRenderingTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_learner_sees_public_escaped_credits_in_a_labelled_modal_only(): void
    {
        [$learner, $lesson, $topic] = $this->fixture();
        $topic->update(['video_attribution' => [
            'original_title' => '<script>alert("title")</script>',
            'creator' => 'Creator & channel',
            'source_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=share',
            'license' => 'cc_by',
            'attribution_text' => '<img src=x onerror=alert(1)>',
            'permission_status' => 'pending',
            'internal_notes' => 'PRIVATE REVIEW DETAIL',
        ]]);
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        $response = $this->actingAs($learner)->get(route('learner.lessons.show', $lesson));
        $response->assertOk()->assertSee('Video source &amp; credits', false)
            ->assertSee('<dialog', false)->assertSee('aria-labelledby="video-source-'.$topic->id.'-title"', false)
            ->assertSee('&lt;script&gt;alert(&quot;title&quot;)&lt;/script&gt;', false)
            ->assertSee('Creator &amp; channel', false)->assertSee('Created by')->assertSee('YouTube')
            ->assertSee('Creative Commons Attribution (CC BY)')
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;feature=share', false)
            ->assertDontSee('PRIVATE REVIEW DETAIL')->assertDontSee('permission_status')->assertDontSee('Permission pending')
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertDontSee('<script>alert("title")</script>', false);
    }

    public function test_legacy_video_has_no_empty_credit_panel_and_still_embeds(): void
    {
        [, , $topic] = $this->fixture();
        $html = $this->renderTopic($topic);
        $this->assertStringNotContainsString('<dialog', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function test_incomplete_and_unsafe_legacy_credits_are_handled_without_private_fields(): void
    {
        [, , $topic] = $this->fixture();
        $topic->update(['video_attribution' => ['creator' => 'Only creator', 'source_url' => 'javascript:alert(1)', 'permission_status' => 'obtained']]);
        $html = $this->renderTopic($topic);
        $this->assertStringContainsString('Only creator', $html);
        $this->assertStringContainsString('Unknown/Not specified', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('Original video title</dt>', $html);
        $this->assertStringNotContainsString('Original source</dt>', $html);
        $topic->update(['video_attribution' => ['permission_status' => 'pending']]);
        $this->assertStringNotContainsString('<dialog', $this->renderTopic($topic));
    }

    public function test_vimeo_local_and_external_files_keep_the_correct_player(): void
    {
        [, , $topic] = $this->fixture();
        $topic->update(['video_provider' => 'vimeo', 'video_id' => '12345678', 'video_attribution' => ['creator' => 'Publisher', 'license' => 'other', 'license_name' => 'Educational use license']]);
        $html = $this->renderTopic($topic);
        $this->assertStringContainsString('https://player.vimeo.com/video/12345678', $html);
        $this->assertStringContainsString('Educational use license', $html);
        $topic->update(['video_provider' => 'local', 'video_id' => null, 'video_file_path' => 'videos/owned.webm', 'video_attribution' => null]);
        $html = $this->renderTopic($topic);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('videos/owned.webm', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<dialog', $html);
        $topic->update(['video_provider' => 'custom', 'video_id' => 'https://media.example.org/video.webm', 'video_file_path' => null]);
        $html = $this->renderTopic($topic);
        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('src="https://media.example.org/video.webm"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $topic->update(['video_id' => 'https://untrusted.example.org/embed']);
        $this->assertStringNotContainsString('<iframe', $this->renderTopic($topic));
    }

    private function renderTopic(LessonTopic $topic): string
    {
        return $this->get(route('learner.lessons.show', $topic->lesson))->assertOk()->getContent();
    }

    private function fixture(): array
    {
        $learner = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $learner->assignRole('learner');
        $learner->gamification()->create(['level' => 1, 'xp' => 0, 'score' => 0, 'current_streak' => 0, 'longest_streak' => 0]);
        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true, 'order' => 1]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'video', 'order' => 1, 'video_provider' => 'youtube', 'video_id' => 'dQw4w9WgXcQ', 'video_file_path' => null, 'text_content' => null]);
        ModuleEnrollment::factory()->create(['user_id' => $learner->id, 'module_id' => $module->id, 'status' => 'approved']);
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        $this->actingAs($learner);

        return [$learner, $lesson, $topic];
    }
}
