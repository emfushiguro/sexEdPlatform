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
            ->withSession(['learning_audio_event' => 'complete'])
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee($lesson->title)
            ->assertSee('data-learning-feedback-host', false)
            ->assertSee('data-learning-audio', false)
            ->assertSee('data-learning-audio-toggle', false)
            ->assertSee(":data-learning-audio-state=\"\$store.learningAudio.enabled ? 'on' : 'off'\"", false)
            ->assertSee('data-learning-audio-event="complete"', false);
    }

    public function test_ordinary_success_toast_remains_without_learning_feedback(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->withSession(['success' => 'Ordinary success'])
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('window.toast.success', false)
            ->assertSee('Ordinary success', false);
    }

    public function test_equivalent_success_toast_is_suppressed_by_valid_learning_feedback(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        foreach ([route('learner.lessons.show', $lesson), route('learner.modules.show', $module)] as $url) {
            $this->actingAs($learner)
            ->withSession([
                'success' => " <i>Lesson</i>\tcompleted\u{FEFF}",
                'learning_feedback' => [
                    'id' => 'lesson:'.$lesson->id.':complete',
                    'sourceId' => 'lesson:'.$lesson->id,
                    'type' => 'lesson_completed',
                    'message' => ' <b>Lesson</b>   completed ',
                    'actions' => [['id' => 'dismiss', 'intent' => 'dismiss', 'label' => 'Dismiss']],
                    'replacesSuccessToast' => true,
                ],
            ])
            ->get($url)
            ->assertOk()
            ->assertSee('data-learning-feedback-host', false)
            ->assertDontSee('window.toast.success', false);
        }
    }

    public function test_malformed_learning_feedback_does_not_suppress_ordinary_success_toast(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->withSession([
                'success' => 'Ordinary success',
                'learning_feedback' => ['replacesSuccessToast' => true],
            ])
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee('window.toast.success', false)
            ->assertSee('Ordinary success', false);
    }

    public function test_non_equivalent_learning_feedback_does_not_suppress_success_in_either_layout(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        foreach ([route('learner.lessons.show', $lesson), route('learner.modules.show', $module)] as $url) {
            $this->actingAs($learner)
                ->withSession([
                    'success' => 'Ordinary success',
                    'learning_feedback' => [
                        'id' => 'activity:1:complete', 'sourceId' => 'activity:1',
                        'type' => 'activity_completed', 'message' => 'A different success',
                        'replacesSuccessToast' => true,
                    ],
                ])
                ->get($url)
                ->assertOk()
                ->assertSee('window.toast.success', false);
        }
    }

    public function test_only_client_whitespace_is_collapsed_when_comparing_success_in_either_layout(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        foreach ([route('learner.lessons.show', $lesson), route('learner.modules.show', $module)] as $url) {
            $this->actingAs($learner)
                ->withSession([
                    'success' => 'Ordinary success',
                    'learning_feedback' => [
                        'id' => 'activity:1:complete', 'sourceId' => 'activity:1',
                        'type' => 'activity_completed', 'message' => "Ordinary\u{0085}success",
                        'replacesSuccessToast' => true,
                    ],
                ])
                ->get($url)
                ->assertOk()
                ->assertSee('window.toast.success', false);
        }
    }

    public function test_equivalent_learning_feedback_requires_explicit_success_replacement_in_either_layout(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        foreach ([false, 'true', 1, null] as $replacesSuccessToast) {
            foreach ([route('learner.lessons.show', $lesson), route('learner.modules.show', $module)] as $url) {
                $this->actingAs($learner)
                    ->withSession([
                        'success' => 'Ordinary success',
                        'learning_feedback' => [
                            'id' => 'activity:1:complete', 'sourceId' => 'activity:1',
                            'type' => 'activity_completed', 'message' => 'Ordinary success',
                            'replacesSuccessToast' => $replacesSuccessToast,
                        ],
                    ])
                    ->get($url)
                    ->assertOk()
                    ->assertSee('window.toast.success', false);
            }
        }
    }

    public function test_markup_only_identifiers_do_not_suppress_success_in_either_layout(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        foreach (['id', 'sourceId'] as $identifier) {
            foreach ([route('learner.lessons.show', $lesson), route('learner.modules.show', $module)] as $url) {
                $this->actingAs($learner)
                    ->withSession([
                        'success' => 'Ordinary success',
                        'learning_feedback' => [
                            'id' => 'activity:1:complete', 'sourceId' => 'activity:1',
                            'type' => 'activity_completed', 'message' => 'Ordinary success',
                            'replacesSuccessToast' => true,
                            $identifier => ' <b></b> ',
                        ],
                    ])
                    ->get($url)
                    ->assertOk()
                    ->assertSee('window.toast.success', false);
            }
        }
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
