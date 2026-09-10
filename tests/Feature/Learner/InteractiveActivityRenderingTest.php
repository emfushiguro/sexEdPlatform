<?php

declare(strict_types=1);

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Enums\InteractiveActivityType;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InteractiveActivity;
use App\Models\InteractiveActivityProgress;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class InteractiveActivityRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_canonical_topic_body_precedes_inside_activity_blocks_in_content_order(): void
    {
        [$learner, $lesson, $topic] = $this->lessonFixture();
        $first = $this->insideActivity($topic, 'First activity');
        $second = $this->insideActivity($topic, 'Second activity');
        $topic->update(['content_blocks' => [
            ['type' => 'interactive_activity', 'uuid' => $first->block_uuid, 'activity_id' => $first->id],
            ['type' => 'interactive_activity', 'uuid' => $second->block_uuid, 'activity_id' => $second->id],
        ]]);

        $html = $this->page($learner, $lesson, 0);

        $this->assertOrdered($html, 'Canonical topic body', 'First activity', 'Second activity');
        $this->assertStringNotContainsString('coming soon', strtolower($html));
    }

    public function test_inside_activities_are_not_added_to_sidebar_as_learning_items(): void
    {
        [$learner, $lesson, $topic] = $this->lessonFixture();
        $activity = $this->insideActivity($topic, 'Inline activity');
        $topic->update(['content_blocks' => [[
            'type' => 'interactive_activity',
            'uuid' => $activity->block_uuid,
            'activity_id' => $activity->id,
        ]]]);

        $html = $this->page($learner, $lesson, 0);

        $this->assertSame(1, substr_count($html, 'Inline activity'));
    }

    public function test_between_activity_uses_optional_sidebar_metadata_without_duration_or_required_label(): void
    {
        [$learner, $lesson, $instructionalTopic] = $this->lessonFixture();
        $host = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive',
            'order' => 2,
            'duration' => 0,
            'interactive_config' => ['placement' => 'between_topics'],
        ]);
        $activity = $this->betweenActivity($host, 'Standalone activity');

        $html = $this->page($learner, $lesson, 1);

        $this->assertStringContainsString('INTERACTIVE ACTIVITY · Optional', $html);
        $row = substr($html, max(0, strpos($html, $host->title) - 100), 700);
        $this->assertStringNotContainsString('0m', $row);
        $this->assertStringNotContainsString('Required', $row);
        $this->assertStringContainsString($activity->title, $html);
        $this->assertNotNull($instructionalTopic);
    }

    public function test_current_revision_completed_standalone_activity_is_skipped_by_default_navigation(): void
    {
        [$learner, $lesson, $instructionalTopic] = $this->lessonFixture();
        $host = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive',
            'order' => 2,
            'duration' => 0,
        ]);
        $activity = $this->betweenActivity($host, 'Resolved activity');
        InteractiveActivityProgress::factory()->create([
            'user_id' => $learner->id,
            'interactive_activity_id' => $activity->id,
            'activity_revision' => $activity->revision,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertViewHas('currentTopic', fn (LessonTopic $topic) => $topic->is($instructionalTopic));
    }

    public function test_old_revision_completion_does_not_resolve_new_standalone_activity_revision(): void
    {
        [$learner, $lesson] = $this->lessonFixture();
        $host = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive',
            'order' => 2,
            'duration' => 0,
        ]);
        $activity = $this->betweenActivity($host, 'Changed activity');
        InteractiveActivityProgress::factory()->create([
            'user_id' => $learner->id,
            'interactive_activity_id' => $activity->id,
            'activity_revision' => $activity->revision,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        $activity->update(['revision' => $activity->revision + 1]);

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', ['lesson' => $lesson, 'topic' => 1]))
            ->assertOk()
            ->assertViewHas('currentTopic', fn (LessonTopic $topic) => $topic->is($host));
    }

    public function test_malformed_inside_activity_is_omitted_while_topic_and_footer_remain(): void
    {
        [$learner, $lesson, $topic] = $this->lessonFixture();
        $activity = $this->insideActivity($topic, 'Broken inline activity', [
            'configuration' => ['schema_version' => 999, 'pairs' => []],
        ]);
        $topic->update(['content_blocks' => [[
            'type' => 'interactive_activity',
            'uuid' => $activity->block_uuid,
            'activity_id' => $activity->id,
        ]]]);

        $html = $this->page($learner, $lesson, 0);

        $this->assertStringContainsString('Canonical topic body', $html);
        $this->assertStringNotContainsString('Broken inline activity', $html);
        $this->assertStringContainsString('data-lesson-footer', $html);
    }

    public function test_malformed_between_activity_shows_unavailable_state_and_continue(): void
    {
        [$learner, $lesson] = $this->lessonFixture();
        $host = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive',
            'order' => 2,
            'duration' => 0,
        ]);
        $this->betweenActivity($host, 'Broken standalone activity', [
            'configuration' => ['schema_version' => 999, 'pairs' => []],
        ]);

        $html = $this->page($learner, $lesson, 1);

        $this->assertStringContainsString('This activity is temporarily unavailable.', $html);
        $this->assertStringContainsString('Continue', $html);
    }

    public function test_only_current_topic_activity_progress_is_initialized(): void
    {
        [$learner, $lesson, $firstTopic] = $this->lessonFixture();
        $first = $this->insideActivity($firstTopic, 'First topic activity');
        $secondTopic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 2,
            'text_content' => '<p>Second topic</p>',
        ]);
        $second = $this->insideActivity($secondTopic, 'Second topic activity');
        $firstTopic->update(['content_blocks' => [[
            'type' => 'interactive_activity', 'uuid' => $first->block_uuid, 'activity_id' => $first->id,
        ]]]);
        $secondTopic->update(['content_blocks' => [[
            'type' => 'interactive_activity', 'uuid' => $second->block_uuid, 'activity_id' => $second->id,
        ]]]);

        $this->page($learner, $lesson, 0);

        $this->assertDatabaseHas('interactive_activity_progress', [
            'user_id' => $learner->id,
            'interactive_activity_id' => $first->id,
        ]);
        $this->assertDatabaseMissing('interactive_activity_progress', [
            'user_id' => $learner->id,
            'interactive_activity_id' => $second->id,
        ]);
    }

    public function test_child_activity_window_listeners_are_scoped_to_their_activity_id(): void
    {
        $renderedActivities = [
            view('learner.lessons.partials.interactive-activities.matching', [
                'activity' => ['id' => 'matching-42', 'payload' => ['left_items' => [], 'right_items' => []]],
            ])->render(),
            view('learner.lessons.partials.interactive-activities.sequencing', [
                'activity' => ['id' => 'sequencing-42', 'payload' => ['items' => []]],
            ])->render(),
        ];

        foreach ($renderedActivities as $html) {
            $this->assertStringContainsString('@interactive-activity-state.window="if ($event.detail.activityId === activityId) status = $event.detail.status"', $html);
            $this->assertStringContainsString('@interactive-activity-payload.window="if ($event.detail.activityId === activityId) loadPayload($event.detail.payload, $event.detail.status, $event.detail.previewToken)"', $html);
            $this->assertStringContainsString('@interactive-activity-practice.window="if ($event.detail.activityId === activityId) ($event.detail.payload ? loadPayload($event.detail.payload, status, $event.detail.previewToken) : resetPractice())"', $html);
        }
    }

    public function test_matching_activity_rehydrates_completed_matches_from_payload(): void
    {
        $html = view('learner.lessons.partials.interactive-activities.matching', [
            'activity' => [
                'id' => 'matching-42',
                'payload' => [
                    'completed_matches' => [['left_id' => 'left-1', 'right_id' => 'right-1']],
                    'left_items' => [],
                    'right_items' => [],
                ],
            ],
        ])->render();

        $this->assertStringContainsString('\\u0022initialMatchedPairs\\u0022:[{\\u0022left_id\\u0022:\\u0022left-1\\u0022,\\u0022right_id\\u0022:\\u0022right-1\\u0022}]', $html);
    }

    public function test_matching_activity_renders_direct_manipulation_endpoints_and_connection_statuses(): void
    {
        $html = view('learner.lessons.partials.interactive-activities.matching', [
            'activity' => ['id' => 'matching-42', 'payload' => [
                'left_items' => [['id' => 'left-1', 'value' => 'One']],
                'right_items' => [['id' => 'right-1', 'value' => 'First']],
            ]],
        ])->render();

        $this->assertStringContainsString('data-match-dot-side="left"', $html);
        $this->assertStringContainsString('data-match-dot-side="right"', $html);
        $this->assertStringContainsString('data-match-id="left-1"', $html);
        $this->assertStringContainsString('data-match-id="right-1"', $html);
        $this->assertStringContainsString('interactive-match-line interactive-match-line--${line.state}', $html);
        $this->assertStringContainsString('interactive-match-arrow-correct', $html);
        $this->assertStringContainsString('interactive-match-arrow-incorrect', $html);
        $this->assertStringContainsString('interactive-match-arrow-pending', $html);
        $this->assertStringContainsString('marker-end', $html);
        $this->assertStringContainsString('aria-label', $html);
        $this->assertStringContainsString('Connect an item to its related item using the dots.', $html);
        $this->assertStringContainsString('>Correct</span>', $html);
        $this->assertStringContainsString('>Incorrect</span>', $html);
        $this->assertStringContainsString(':aria-disabled="String(!isEndpointAvailable(', $html);
        $this->assertStringContainsString('Remove incorrect connection', $html);
        $this->assertStringNotContainsString('Check match', $html);
        $this->assertStringNotContainsString('data-match-left=', $html);
        $this->assertStringNotContainsString('data-match-right=', $html);
        $this->assertStringNotContainsString('lg:hidden', $html);
    }

    public function test_sequencing_activity_renders_handle_dragging_without_visible_move_buttons(): void
    {
        $html = view('learner.lessons.partials.interactive-activities.sequencing', [
            'activity' => ['id' => 'sequencing-42', 'payload' => [
                'items' => [
                    ['id' => 'item-1', 'value' => 'First'],
                    ['id' => 'item-2', 'value' => 'Second'],
                ],
            ]],
        ])->render();

        $this->assertStringContainsString('sequencing-drag-instructions', $html);
        $this->assertStringContainsString('class="mb-3 text-sm text-gray-600"', $html);
        $this->assertStringContainsString('Use Space or Enter to pick up an item.', $html);
        $this->assertStringContainsString('aria-describedby="sequencing-drag-instructions"', $html);
        $this->assertStringContainsString('@pointerdown.prevent.stop="beginPointerDrag(index, $event)"', $html);
        $this->assertStringContainsString('aria-pressed="isDragging() && draggedId === itemId"', $html);
        $this->assertStringContainsString('x-show="isDragging()"', $html);
        $this->assertStringContainsString('x-text="dragAnnouncement"', $html);
        $this->assertStringNotContainsString('Move First up', $html);
        $this->assertStringNotContainsString('Move First down', $html);
        $this->assertStringNotContainsString('>â†‘<', $html);
        $this->assertStringNotContainsString('>â†“<', $html);
    }

    public function test_activity_controls_have_minimum_hit_targets_and_visible_focus_styles(): void
    {
        $renderedControls = [
            [
                view('learner.lessons.partials.interactive-activities.matching', [
                    'activity' => ['id' => 'matching-42', 'payload' => [
                        'left_items' => [['id' => 'left-1', 'value' => 'One']],
                        'right_items' => [['id' => 'right-1', 'value' => 'First']],
                    ]],
                ])->render(),
                3,
            ],
            [
                view('learner.lessons.partials.interactive-activities.sequencing', [
                    'activity' => ['id' => 'sequencing-42', 'payload' => [
                        'items' => [['id' => 'item-1', 'value' => 'First']],
                    ]],
                ])->render(),
                2,
            ],
            [
                view('learner.lessons.partials.interactive-activities.shell', [
                    'activity' => [
                        'id' => 'matching-42',
                        'available' => true,
                        'type' => 'matching',
                        'payload' => ['left_items' => [], 'right_items' => []],
                    ],
                ])->render(),
                5,
            ],
        ];

        foreach ($renderedControls as [$html, $buttonCount]) {
            $this->assertSame($buttonCount, substr_count($html, 'min-h-11'));
            $this->assertSame($buttonCount, substr_count($html, 'focus-visible:outline-purple-700'));
        }
    }

    public function test_activity_shell_centralizes_accessible_feedback_and_completed_explanation(): void
    {
        foreach (['matching', 'sequencing'] as $type) {
            $html = view('learner.lessons.partials.interactive-activities.shell', [
                'activity' => [
                    'id' => "{$type}-42",
                    'available' => true,
                    'type' => $type,
                    'status' => 'completed',
                    'instructions' => '<p>Choose a <strong>pair</strong>.</p>',
                    'explanation' => '<p>Pairs belong together.</p>',
                    'payload' => ['left_items' => [], 'right_items' => [], 'items' => []],
                ],
            ])->render();

            $this->assertSame(1, substr_count($html, 'x-show="feedback.message" aria-label="Activity feedback" aria-live="polite" role="status"'));
            $this->assertStringContainsString('interactive-activity-container', $html);
            $this->assertStringContainsString('@interactive-activity-result.window="if ($event.detail.activityId === activityId) handleActivityResult($event.detail)"', $html);
            $this->assertStringContainsString('@interactive-activity-error.window="if ($event.detail.activityId === activityId) handleActivityError($event.detail)"', $html);
            $this->assertStringContainsString('@interactive-activity-recovered.window="if ($event.detail.activityId === activityId) handleActivityRecovered($event.detail)"', $html);
            $this->assertStringContainsString('x-show="feedback.message"', $html);
            $this->assertStringContainsString('role="status"', $html);
            $this->assertStringContainsString('x-text="feedback.message"', $html);
            $this->assertStringContainsString('x-show="error" role="alert"', $html);
            $this->assertStringContainsString("x-show=\"['completed', 'practice_completed'].includes(status) && explanation\"", $html);
            $this->assertStringContainsString('x-html="explanation"', $html);
            $this->assertStringContainsString('<p>Choose a <strong>pair</strong>.</p>', $html);
        }
    }

    /** @return array{User, Lesson, LessonTopic} */
    private function lessonFixture(): array
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $module = Module::factory()->create(['is_published' => true, 'current_review_status' => null]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'text_content' => '<p>Canonical topic body</p>',
            'content_blocks' => [],
        ]);
        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        return [$learner, $lesson, $topic];
    }

    /** @param array<string, mixed> $overrides */
    private function insideActivity(LessonTopic $topic, string $title, array $overrides = []): InteractiveActivity
    {
        return InteractiveActivity::create(array_merge([
            'lesson_topic_id' => $topic->id,
            'placement' => 'inside_topic',
            'block_uuid' => (string) Str::uuid(),
            'activity_type' => InteractiveActivityType::MATCHING,
            'title' => $title,
            'instructions' => 'Choose each matching pair.',
            'configuration' => $this->matchingConfiguration(),
            'revision' => 1,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function betweenActivity(LessonTopic $topic, string $title, array $overrides = []): InteractiveActivity
    {
        return $this->insideActivity($topic, $title, array_merge([
            'placement' => 'between_topics',
            'block_uuid' => null,
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function matchingConfiguration(): array
    {
        return [
            'schema_version' => 1,
            'pairs' => [
                ['id' => 'pair-1', 'left' => ['id' => 'left-1', 'kind' => 'text', 'value' => 'One'], 'right' => ['id' => 'right-1', 'kind' => 'text', 'value' => 'First']],
                ['id' => 'pair-2', 'left' => ['id' => 'left-2', 'kind' => 'text', 'value' => 'Two'], 'right' => ['id' => 'right-2', 'kind' => 'text', 'value' => 'Second']],
            ],
        ];
    }

    private function page(User $learner, Lesson $lesson, int $topicIndex): string
    {
        return $this->actingAs($learner)
            ->get(route('learner.lessons.show', ['lesson' => $lesson, 'topic' => $topicIndex]))
            ->assertOk()
            ->getContent();
    }

    private function assertOrdered(string $html, string ...$needles): void
    {
        $positions = array_map(fn (string $needle) => strpos($html, $needle), $needles);
        foreach ($positions as $position) {
            $this->assertNotFalse($position);
        }
        foreach ($positions as $index => $position) {
            if ($index > 0) {
                $this->assertLessThan($position, $positions[$index - 1]);
            }
        }
    }
}
