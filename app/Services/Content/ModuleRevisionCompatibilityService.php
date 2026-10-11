<?php

namespace App\Services\Content;

use App\Models\Module;
use App\Models\ModuleRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleRevisionCompatibilityService
{
    public function assertCompatible(Module $module, ModuleRevision $candidate): void
    {
        if ((int) $candidate->module_id !== (int) $module->id) {
            throw ValidationException::withMessages([
                'revision' => 'The candidate revision does not belong to this module.',
            ]);
        }

        $published = $module->publishedRevision;
        if (!$published) {
            return;
        }

        $current = $this->identifiers($published->snapshot_payload);
        $next = $this->identifiers($candidate->snapshot_payload);
        $errors = [];

        foreach ([
            'lessons' => 'lessons',
            'topics' => 'topics',
            'interactive_activities' => 'interactive activities',
            'quizzes' => 'quizzes',
        ] as $type => $label) {
            $removed = array_values(array_diff($current[$type], $next[$type]));
            $withHistory = $this->identifiersWithHistory($module, $type, $removed);

            if ($withHistory !== []) {
                $errors[$type] = sprintf(
                    'This revision removes %d %s with persisted learner history: %s.',
                    count($withHistory),
                    $label,
                    implode(', ', $withHistory),
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, array<int, int>>
     */
    private function identifiers(?array $snapshot): array
    {
        $lessons = collect($snapshot['lessons'] ?? []);
        $lessonIds = $lessons
            ->map(fn ($lesson) => data_get($lesson, 'attributes.id', data_get($lesson, 'id')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $topicIds = $lessons
            ->flatMap(fn ($lesson) => (array) data_get($lesson, 'topics', []))
            ->map(fn ($topic) => data_get($topic, 'id', data_get($topic, 'attributes.id')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $activityIds = $lessons
            ->flatMap(fn ($lesson) => (array) data_get($lesson, 'topics', []))
            ->flatMap(fn ($topic) => (array) data_get($topic, 'interactive_activities', data_get($topic, 'interactiveActivities', [])))
            ->map(fn ($activity) => data_get($activity, 'id', data_get($activity, 'attributes.id')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $quizIds = collect($snapshot['quizzes'] ?? [])
            ->map(fn ($quiz) => data_get($quiz, 'attributes.id', data_get($quiz, 'id')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            'lessons' => $lessonIds,
            'topics' => $topicIds,
            'interactive_activities' => $activityIds,
            'quizzes' => $quizIds,
        ];
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function identifiersWithHistory(Module $module, string $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $query = match ($type) {
            'lessons' => DB::table('user_progress')
                ->where('module_id', $module->id)
                ->whereIn('lesson_id', $ids)
                ->distinct()
                ->pluck('lesson_id'),
            'topics' => DB::table('lesson_topic_progress')
                ->whereIn('lesson_topic_id', $ids)
                ->distinct()
                ->pluck('lesson_topic_id'),
            'interactive_activities' => DB::table('interactive_activity_progress')
                ->whereIn('interactive_activity_id', $ids)
                ->distinct()
                ->pluck('interactive_activity_id'),
            'quizzes' => DB::table('quiz_attempts')
                ->whereIn('quiz_id', $ids)
                ->distinct()
                ->pluck('quiz_id'),
            default => collect(),
        };

        return $query
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }
}
