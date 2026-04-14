<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    private function enrolledLearnerWithLesson(): array
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');
        $learner->gamification()->create([
            'level' => 1, 'xp' => 0, 'score' => 0,
            'current_streak' => 0, 'longest_streak' => 0,
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'is_prerequisite' => false,
        ]);
        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        return compact('learner', 'module', 'lesson', 'topic');
    }

    public function test_learner_can_view_lesson_show_page(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();
        /** @var User $learner */

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee($lesson->title);
    }

    public function test_lesson_page_does_not_contain_about_this_lesson_block(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();
        /** @var User $learner */

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertDontSee('About this lesson');
    }

    public function test_lesson_page_contains_back_url_for_module(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();
        /** @var User $learner */

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee(route('learner.modules.show', $module), false);
    }

    public function test_text_topic_page_renders_without_error(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');
        $learner->gamification()->create([
            'level' => 1, 'xp' => 0, 'score' => 0,
            'current_streak' => 0, 'longest_streak' => 0,
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'text_content' => '<p>Hello world</p>',
            'image_attachments' => null,
        ]);
        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('Hello world', false);
    }
}
