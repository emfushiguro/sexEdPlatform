<?php

declare(strict_types=1);

namespace App\Services\Learning\InteractiveActivities;

use App\Enums\InteractiveActivityType;
use App\Models\InteractiveActivity;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class InteractiveActivityAuthoringService
{
    private const ALLOWED_HTML = '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><code>';

    private const PREVIEW_TTL_SECONDS = 900;

    public function __construct(private readonly InteractiveActivityRegistry $registry) {}

    public function validate(\Illuminate\Http\Request $request, Lesson $lesson, ?InteractiveActivity $activity = null): array
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:interactive'],
            'activity_type' => ['required', 'in:matching,sequencing'],
            'placement' => ['required', 'in:inside_topic,between_topics'],
            'parent_topic_id' => ['nullable', 'integer'],
            'insert_after_block' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'configuration' => ['required', 'array'],
        ]);

        $handler = $activity
            ? $this->registry->for($activity->activity_type)
            : $this->registry->for($validated['activity_type']);
        if ($activity && $validated['activity_type'] !== $activity->activity_type->value) {
            throw ValidationException::withMessages([
                'activity_type' => 'Activity type cannot be changed after creation.',
            ]);
        }
        $configuration = $this->addDefaultTextKinds($validated['configuration'], $validated['activity_type']);
        $configuration = Validator::make(['configuration' => $configuration], $handler->rules())->validate()['configuration'];
        $normalized = $handler->normalize($configuration, $activity?->configuration);

        return [
            'lesson_id' => $lesson->id,
            'title' => trim($validated['title']),
            'type' => 'interactive',
            'activity_type' => $validated['activity_type'],
            'placement' => $validated['placement'],
            'parent_topic_id' => $validated['parent_topic_id'] ?? null,
            'insert_after_block' => $validated['insert_after_block'] ?? null,
            'instructions' => $this->sanitize($validated['instructions'] ?? null),
            'explanation' => $this->sanitize($validated['explanation'] ?? null),
            'configuration' => $normalized,
        ];
    }

    public function create(Lesson $lesson, array $data): InteractiveActivity
    {
        return DB::transaction(function () use ($lesson, $data): InteractiveActivity {
            if ($data['placement'] === 'inside_topic') {
                $parent = $lesson->topics()
                    ->instructional()
                    ->find($data['parent_topic_id'] ?? null);

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_topic_id' => 'Choose an eligible topic in this lesson.',
                    ]);
                }

                Gate::authorize('update', $parent);

                $blockUuid = (string) Str::uuid();
                $activity = $this->createActivity($parent, $data, 'inside_topic', $blockUuid);
                $blocks = $this->blocksForTopic($parent);
                $insertAfter = (int) ($data['insert_after_block'] ?? 0);
                array_splice($blocks, min(max(0, $insertAfter) + 1, count($blocks)), 0, [[
                    'type' => 'interactive_activity',
                    'uuid' => $blockUuid,
                    'activity_id' => $activity->id,
                ]]);
                $parent->update(['content_blocks' => array_values($blocks)]);
            } else {
                $topic = $lesson->topics()->create([
                    'title' => $data['title'],
                    'type' => 'interactive',
                    'duration' => 0,
                    'is_prerequisite' => false,
                    'order' => ($lesson->topics()->max('order') ?? 0) + 1,
                    'interactive_config' => ['placement' => 'between_topics'],
                ]);
                $activity = $this->createActivity($topic, $data, 'between_topics', null);
            }

            $this->recalculateDurations($lesson);

            return $activity->fresh();
        });
    }

    public function update(InteractiveActivity $activity, array $data): InteractiveActivity
    {
        return DB::transaction(function () use ($activity, $data): InteractiveActivity {
            $stored = InteractiveActivity::query()
                ->lockForUpdate()
                ->findOrFail($activity->id);
            $currentTopic = $stored->lessonTopic()->lockForUpdate()->firstOrFail();
            $lesson = $currentTopic->lesson()->firstOrFail();
            $handler = $this->registry->for($stored->activity_type);
            $configuration = $handler->normalize($data['configuration'], $stored->configuration);
            $nextRevision = $handler->answerFingerprint($stored->configuration) !== $handler->answerFingerprint($configuration)
                ? ((int) $stored->revision) + 1
                : (int) $stored->revision;
            $targetPlacement = $data['placement'] ?? $stored->placement;
            $oldPlacement = $stored->placement;
            $oldTopic = $currentTopic;
            $targetTopic = $currentTopic;
            $targetBlockUuid = null;
            $oldHostToDelete = null;

            if ($targetPlacement === 'inside_topic') {
                $targetTopic = $lesson->topics()
                    ->instructional()
                    ->lockForUpdate()
                    ->find($data['parent_topic_id'] ?? null);

                if (! $targetTopic) {
                    throw ValidationException::withMessages([
                        'parent_topic_id' => 'Choose an eligible topic in this lesson.',
                    ]);
                }

                Gate::authorize('update', $targetTopic);
                $sameParent = $oldPlacement === 'inside_topic' && $oldTopic->is($targetTopic);
                $targetBlockUuid = $sameParent ? $stored->block_uuid : (string) Str::uuid();

                if ($sameParent) {
                    $insertAfter = (int) ($data['insert_after_block'] ?? 0);
                    $currentBlockIndex = null;
                    foreach ($this->blocksForTopic($targetTopic) as $blockIndex => $block) {
                        if (is_array($block) && ($block['uuid'] ?? null) === $stored->block_uuid && (int) ($block['activity_id'] ?? 0) === (int) $stored->id) {
                            $currentBlockIndex = $blockIndex;
                            break;
                        }
                    }

                    if ($currentBlockIndex !== null && $currentBlockIndex <= $insertAfter) {
                        $insertAfter--;
                    }

                    $targetTopic->update(['content_blocks' => $this->removeActivityBlock($targetTopic, $stored)]);
                    $this->addActivityBlock($targetTopic, $targetBlockUuid, $stored->id, $insertAfter);
                } else {
                    if ($oldPlacement === 'inside_topic') {
                        $oldTopic->update(['content_blocks' => $this->removeActivityBlock($oldTopic, $stored)]);
                    } else {
                        $oldHostToDelete = $oldTopic;
                    }

                    $this->addActivityBlock($targetTopic, $targetBlockUuid, $stored->id, (int) ($data['insert_after_block'] ?? 0));
                }
            } else {
                if ($oldPlacement === 'between_topics') {
                    $targetTopic = $oldTopic;
                } else {
                    $targetTopic = $lesson->topics()->create([
                        'title' => trim((string) $data['title']),
                        'type' => 'interactive',
                        'duration' => 0,
                        'is_prerequisite' => false,
                        'order' => ($lesson->topics()->max('order') ?? 0) + 1,
                        'interactive_config' => ['placement' => 'between_topics'],
                    ]);
                    $oldTopic->update(['content_blocks' => $this->removeActivityBlock($oldTopic, $stored)]);
                }

                $targetTopic->update([
                    'title' => trim((string) $data['title']),
                    'type' => 'interactive',
                    'duration' => 0,
                    'is_prerequisite' => false,
                    'interactive_config' => ['placement' => 'between_topics'],
                ]);
            }

            $stored->update([
                'lesson_topic_id' => $targetTopic->id,
                'placement' => $targetPlacement,
                'block_uuid' => $targetBlockUuid,
                'title' => trim((string) $data['title']),
                'instructions' => $this->sanitize($data['instructions'] ?? null),
                'explanation' => $this->sanitize($data['explanation'] ?? null),
                'configuration' => $configuration,
                'revision' => $nextRevision,
            ]);

            if ($oldHostToDelete) {
                $oldHostToDelete->delete();
            }

            $this->resequenceLesson($lesson);
            $this->recalculateDurations($lesson);

            return $stored->fresh();
        });
    }

    public function delete(InteractiveActivity $activity): void
    {
        DB::transaction(function () use ($activity): void {
            $stored = InteractiveActivity::query()->lockForUpdate()->findOrFail($activity->id);
            $topic = $stored->lessonTopic()->lockForUpdate()->firstOrFail();
            $lesson = $topic->lesson()->firstOrFail();

            if ($stored->placement === 'inside_topic') {
                $topic->update(['content_blocks' => $this->removeActivityBlock($topic, $stored)]);
                $stored->delete();
            } else {
                $stored->delete();
                $topic->delete();
            }

            $this->resequenceLesson($lesson);
            $this->recalculateDurations($lesson);
        });
    }

    public function preview(Lesson $lesson, array $data, User $author): array
    {
        if ($data['placement'] === 'inside_topic') {
            $parent = $lesson->topics()
                ->instructional()
                ->find($data['parent_topic_id'] ?? null);

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_topic_id' => 'Choose an eligible topic in this lesson.',
                ]);
            }

            Gate::authorize('update', $parent);
        }

        $handler = $this->registry->for($data['activity_type']);
        $configuration = $handler->normalize(
            $this->addDefaultTextKinds($data['configuration'], $data['activity_type']),
        );
        $workingState = $handler->initialWorkingState($configuration, new Randomizer(new Mt19937(1234)));
        $previewContext = [
            'version' => 1,
            'author_id' => $author->id,
            'lesson_id' => $lesson->id,
            'activity_type' => $data['activity_type'],
            'configuration' => $configuration,
            'working_state' => $workingState,
            'explanation' => $data['explanation'] ?? null,
            'expires_at' => now()->addSeconds(self::PREVIEW_TTL_SECONDS)->timestamp,
        ];

        return [
            'id' => 'preview-'.Str::uuid(),
            'revision' => 1,
            'type' => $data['activity_type'],
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'explanation' => $data['explanation'] ?? null,
            'status' => 'practice',
            'available' => true,
            'preview' => true,
            'payload' => $handler->previewPayload($configuration, $workingState),
            'preview_token' => $this->issuePreviewToken($previewContext),
        ];
    }

    /** @return array<string, mixed> */
    public function decodePreviewToken(string $token, User $author): array
    {
        try {
            $context = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            $this->throwInvalidPreviewToken();
        }

        if (! is_array($context)
            || ($context['version'] ?? null) !== 1
            || (int) ($context['author_id'] ?? 0) !== (int) $author->id
            || ! is_int($context['lesson_id'] ?? null)
            || ! is_string($context['activity_type'] ?? null)
            || ! is_array($context['configuration'] ?? null)
            || ! is_array($context['working_state'] ?? null)
            || ! (is_string($context['explanation'] ?? null) || $context['explanation'] === null)
            || ! is_int($context['expires_at'] ?? null)
            || $context['expires_at'] < now()->timestamp
        ) {
            if (is_array($context) && (int) ($context['author_id'] ?? 0) !== (int) $author->id) {
                throw new AuthorizationException('You may not use this activity preview.');
            }

            $this->throwInvalidPreviewToken();
        }

        $type = InteractiveActivityType::tryFrom($context['activity_type']);
        if ($type === null) {
            $this->throwInvalidPreviewToken();
        }

        try {
            $handler = $this->registry->for($type);
            $context['configuration'] = $handler->normalize($context['configuration'], $context['configuration']);
        } catch (\Throwable) {
            $this->throwInvalidPreviewToken();
        }

        return $context;
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $answer @return array<string, mixed> */
    public function evaluatePreview(array $context, array $answer): array
    {
        $type = InteractiveActivityType::from($context['activity_type']);
        $handler = $this->registry->for($type);
        $configuration = $context['configuration'];
        $action = $answer['action'] ?? null;

        if ($action === 'practice') {
            $workingState = $handler->initialWorkingState($configuration, new Randomizer(new Mt19937(random_int(1, 2147483647))));
            $result = [
                'accepted' => true,
                'is_correct' => null,
                'is_complete' => false,
                'working_state' => $workingState,
            ];
        } else {
            $expectedAction = $type === InteractiveActivityType::MATCHING ? 'match' : 'check_sequence';
            if ($action !== $expectedAction) {
                throw ValidationException::withMessages(['action' => 'The Preview action does not match the activity type.']);
            }

            $result = $handler->evaluate($configuration, $answer, $context['working_state']);
            $workingState = $result['working_state'];
        }

        return [
            ...$result,
            'status' => $result['is_complete'] === true ? 'practice_completed' : 'practice',
            'payload' => $handler->learnerPayload($configuration, $workingState),
            'explanation' => $result['is_complete'] === true ? ($context['explanation'] ?? null) : null,
            'preview_token' => $this->issuePreviewToken([
                ...$context,
                'working_state' => $workingState,
                'expires_at' => now()->addSeconds(self::PREVIEW_TTL_SECONDS)->timestamp,
            ]),
        ];
    }

    /** @param array<string, mixed> $context */
    private function issuePreviewToken(array $context): string
    {
        return Crypt::encryptString(json_encode($context, JSON_THROW_ON_ERROR));
    }

    private function throwInvalidPreviewToken(): never
    {
        throw ValidationException::withMessages([
            'preview_token' => 'The activity preview expired or is invalid. Generate a new preview.',
        ]);
    }

    private function createActivity(LessonTopic $topic, array $data, string $placement, ?string $blockUuid): InteractiveActivity
    {
        return $topic->interactiveActivities()->create([
            'placement' => $placement,
            'block_uuid' => $blockUuid,
            'activity_type' => $data['activity_type'],
            'title' => $data['title'],
            'instructions' => $data['instructions'],
            'explanation' => $data['explanation'],
            'configuration' => $data['configuration'],
            'revision' => 1,
        ]);
    }

    private function recalculateDurations(Lesson $lesson): void
    {
        $lesson->update(['duration' => $lesson->topics()->instructional()->sum('duration')]);
        $lesson->module?->update([
            'duration_minutes' => $lesson->module->lessons()->sum('duration'),
        ]);
    }

    private function blocksForTopic(LessonTopic $topic): array
    {
        if (is_array($topic->content_blocks) && count($topic->content_blocks) > 0) {
            return $topic->content_blocks;
        }

        return [[
            'type' => 'rich_text',
            'html' => $topic->text_content ?? '',
        ]];
    }

    private function addActivityBlock(LessonTopic $topic, string $blockUuid, int $activityId, int $insertAfter): void
    {
        $blocks = $this->blocksForTopic($topic);
        array_splice($blocks, $this->activityInsertionOffset($blocks, $insertAfter), 0, [[
            'type' => 'interactive_activity',
            'uuid' => $blockUuid,
            'activity_id' => $activityId,
        ]]);
        $topic->update(['content_blocks' => array_values($blocks)]);
    }

    private function activityInsertionOffset(array $blocks, int $insertAfter): int
    {
        if ($insertAfter <= 0) {
            foreach ($blocks as $index => $block) {
                if (is_array($block) && in_array($block['type'] ?? null, ['checkpoint', 'interactive_activity'], true)) {
                    return $index;
                }
            }

            return count($blocks);
        }

        return min($insertAfter + 1, count($blocks));
    }

    private function removeActivityBlock(LessonTopic $topic, InteractiveActivity $activity): array
    {
        return array_values(array_filter(
            $this->blocksForTopic($topic),
            static fn ($block): bool => ! (
                is_array($block)
                && ($block['type'] ?? null) === 'interactive_activity'
                && ($block['uuid'] ?? null) === $activity->block_uuid
                && (int) ($block['activity_id'] ?? 0) === (int) $activity->id
            ),
        ));
    }

    private function resequenceLesson(Lesson $lesson): void
    {
        foreach ($lesson->topics()->orderBy('order')->orderBy('id')->get() as $index => $topic) {
            $topic->update(['order' => $index + 1]);
        }
    }

    private function addDefaultTextKinds(array $configuration, string $activityType): array
    {
        if ($activityType === InteractiveActivityType::MATCHING->value) {
            $pairs = $configuration['pairs'] ?? null;
            if (! is_array($pairs)) {
                return $configuration;
            }

            foreach ($pairs as $index => $pair) {
                if (! is_array($pair['left'] ?? null) || ! is_array($pair['right'] ?? null)) {
                    continue;
                }

                $configuration['pairs'][$index]['left']['kind'] ??= 'text';
                $configuration['pairs'][$index]['right']['kind'] ??= 'text';
            }
        } else {
            $items = $configuration['items'] ?? null;
            if (! is_array($items)) {
                return $configuration;
            }

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $configuration['items'][$index]['kind'] ??= 'text';
            }
        }

        return $configuration;
    }

    private function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $allowed = strip_tags($html, self::ALLOWED_HTML);

        return preg_replace_callback('/<([a-z][a-z0-9]*)(?:\s[^>]*)?>/i', static function (array $match): string {
            $tag = strtolower($match[1]);
            if ($tag !== 'a') {
                return '<'.$tag.'>';
            }

            preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $match[0], $hrefMatch);
            $href = $hrefMatch[2] ?? '';
            if ($href === '' || ! preg_match('/^(?:https?:|mailto:|\/|#)/i', $href)) {
                return '<a>';
            }

            return '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
        }, $allowed) ?? '';
    }
}
