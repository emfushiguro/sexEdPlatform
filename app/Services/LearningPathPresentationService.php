<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\ModuleEnrollment;
use App\Models\ModulePurchase;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class LearningPathPresentationService
{
    public function __construct(
        private readonly LearnerModuleCompletionService $completionService,
    ) {
    }

    /**
     * @param  Collection<int, LearningPath>  $paths
     * @return array<int, array<string, mixed>>
     */
    public function summariesFor(User $user, Collection $paths): array
    {
        $presentations = $this->presentMany($user, $paths);
        $summaries = [];

        foreach ($presentations as $pathId => $presentation) {
            $summaries[(int) $pathId] = $this->summary($presentation);
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    public function presentFor(User $user, LearningPath $path): array
    {
        return $this->presentMany($user, collect([$path]))[(int) $path->id];
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(LearningPath $path): array
    {
        $path->loadMissing(['pathModules.module']);
        $nodes = [];

        foreach ($path->pathModules as $index => $membership) {
            $module = $membership->module;
            if (! $module) {
                continue;
            }

            $position = count($nodes) + 1;
            $nodes[] = [
                'module' => $module,
                'position' => $position,
                'state' => $position === 1 ? 'recommended' : 'available',
                'state_label' => $position === 1 ? 'Recommended Next' : 'Available',
                'reason' => null,
                'progress_percentage' => 0,
                'completed_lessons' => 0,
                'total_lessons' => 0,
                'is_current' => $position === 1,
                'action_url' => $this->routeIfAvailable('learner.modules.show', $module),
                'action_label' => $position === 1 ? 'View module' : 'View module',
            ];
        }

        return [
            'path' => $path,
            'nodes' => $nodes,
            'progress_percentage' => 0,
            'completed_modules' => 0,
            'actionable_modules' => count($nodes),
            'total_modules' => count($nodes),
            'completed_path' => false,
            'current' => $nodes[0] ?? null,
            'recommended' => $nodes[0] ?? null,
            'action_url' => $nodes[0]['action_url'] ?? null,
            'action_label' => $nodes[0]['action_label'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, LearningPath>  $paths
     * @return array<int, array<string, mixed>>
     */
    private function presentMany(User $user, Collection $paths): array
    {
        $paths = $this->loadPathRelations($paths->values());
        $memberships = $paths->flatMap(fn (LearningPath $path) => $path->pathModules);
        $modules = $memberships->pluck('module')->filter()->unique('id')->values();
        $moduleIds = $modules->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $lessonsByModule = $this->publishedLessonsByModule($moduleIds);
        $allTopics = $lessonsByModule->flatMap(fn (Collection $lessons) => $lessons->flatMap->topics);
        $completedTopicIds = $allTopics->isNotEmpty()
            ? $this->completionService->completedTopicIds($user, $allTopics)
            : collect();
        $enrollments = $this->enrollmentsFor($user, $moduleIds);
        $purchases = $this->purchasesFor($user, $moduleIds);
        $completedProgress = $this->completedProgressFor($user, $moduleIds);
        $ageBracket = $user->learnerProfile?->getAgeBracket();

        $presentations = [];
        foreach ($paths as $path) {
            $nodes = [];

            foreach ($path->pathModules as $membership) {
                $module = $membership->module;
                if (! $module) {
                    continue;
                }

                $enrollment = $enrollments->get((int) $module->id);
                $status = $this->enrollmentStatus($enrollment);
                $approved = $status === EnrollmentStatus::Approved->value;
                $activeVisible = ! $module->trashed()
                    && $module->isLearnerVisible()
                    && $this->isAgeAppropriate($module, $ageBracket);

                // Hidden modules without approved historical access must not leak into a path.
                if (! $approved && ! $activeVisible) {
                    continue;
                }

                $lessons = $lessonsByModule->get((int) $module->id, collect());
                $lessonIds = $lessons->pluck('id')->map(fn ($id): int => (int) $id);
                $completedLessonIds = $completedProgress->get((int) $module->id, collect());
                $completedLessons = $approved
                    ? $lessonIds->intersect($completedLessonIds)->count()
                    : 0;
                $totalLessons = $lessons->count();
                $canonical = $approved
                    && ($enrollment?->completed_at !== null || (int) ($enrollment?->completion_percentage ?? 0) >= 100);
                $instructionalTopics = $lessons
                    ->flatMap(fn (Lesson $lesson) => $lesson->topics)
                    ->filter(fn ($topic): bool => ! in_array($topic->type, ['interactive', 'interactive_checkpoint'], true));
                $progressPercentage = $this->progressPercentage(
                    $approved,
                    $canonical,
                    $instructionalTopics,
                    $completedTopicIds,
                    $lessonIds,
                    $completedLessonIds,
                );

                $reason = $this->unavailableReason($status, $approved, $activeVisible, $module);
                $node = [
                    'module' => $module,
                    'position' => count($nodes) + 1,
                    'state' => $canonical ? 'completed' : ($reason === null ? 'available' : 'unavailable'),
                    'state_label' => $canonical ? 'Completed' : ($reason === null ? 'Available' : 'Unavailable'),
                    'reason' => $reason,
                    'progress_percentage' => $progressPercentage,
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'is_current' => false,
                    'action_url' => $this->routeIfAvailable('learner.modules.show', $module),
                    'action_label' => $canonical ? 'Review module' : 'View module',
                    'has_purchased' => $purchases->has((int) $module->id),
                ];

                if ($node['state'] !== 'completed' && $node['state'] !== 'unavailable' && $approved && $progressPercentage > 0) {
                    $node['state'] = 'in_progress';
                    $node['state_label'] = 'In Progress';
                }

                if ($node['state'] !== 'unavailable' && $approved) {
                    $nextLesson = $lessons->first(fn (Lesson $lesson): bool => ! $completedLessonIds->contains((int) $lesson->id));
                    if ($nextLesson) {
                        $node['action_url'] = $this->routeIfAvailable('learner.lessons.show', $nextLesson);
                        $node['action_label'] = $node['state'] === 'in_progress' ? 'Continue lesson' : 'Start lesson';
                    }
                }

                $nodes[] = $node;
            }

            $current = collect($nodes)->first(fn (array $node): bool => $node['state'] === 'in_progress')
                ?? collect($nodes)->first(fn (array $node): bool => $node['state'] === 'available');
            $recommendedIndex = collect($nodes)->search(fn (array $node): bool => $node['state'] === 'available');
            if ($current && $recommendedIndex !== false && ! collect($nodes)->contains(fn (array $node): bool => $node['state'] === 'in_progress')) {
                $nodes[$recommendedIndex]['state'] = 'recommended';
                $nodes[$recommendedIndex]['state_label'] = 'Recommended Next';
                $nodes[$recommendedIndex]['action_label'] = $nodes[$recommendedIndex]['action_label'] === 'View module'
                    ? 'View module'
                    : $nodes[$recommendedIndex]['action_label'];
                $current = $nodes[$recommendedIndex];
            }

            $currentModuleId = $current['module']->id ?? null;
            foreach ($nodes as $index => $node) {
                $nodes[$index]['is_current'] = $currentModuleId !== null
                    && (int) $node['module']->id === (int) $currentModuleId;
            }

            $actionableNodes = collect($nodes)->reject(fn (array $node): bool => $node['state'] === 'unavailable');
            $completedNodes = $actionableNodes->where('state', 'completed')->count();
            $actionableCount = $actionableNodes->count();
            $completedPath = $actionableCount > 0 && $completedNodes === $actionableCount;
            $recommended = collect($nodes)->firstWhere('state', 'recommended');
            $current = $currentModuleId === null
                ? null
                : collect($nodes)->first(fn (array $node): bool => $node['is_current']);

            $presentations[(int) $path->id] = [
                'path' => $path,
                'nodes' => $nodes,
                'progress_percentage' => $actionableCount > 0 ? (int) round(($completedNodes / $actionableCount) * 100) : 0,
                'completed_modules' => $completedNodes,
                'actionable_modules' => $actionableCount,
                'total_modules' => count($nodes),
                'completed_path' => $completedPath,
                'current' => $current,
                'recommended' => $recommended,
                'action_url' => $completedPath
                    ? $this->routeIfAvailable('learner.learning-paths.show', $path)
                    : ($current['action_url'] ?? null),
                'action_label' => $completedPath ? 'Review path' : ($current['action_label'] ?? null),
            ];
        }

        return $presentations;
    }

    /**
     * @param  Collection<int, LearningPath>  $paths
     * @return Collection<int, LearningPath>
     */
    private function loadPathRelations(Collection $paths): Collection
    {
        $eloquentPaths = new EloquentCollection($paths->all());
        $eloquentPaths->loadMissing([
            'pathModules.module.learnerCategories',
            'pathModules.module.creator',
        ]);

        return collect($eloquentPaths->all());
    }

    /**
     * @param  array<int, int>  $moduleIds
     * @return Collection<int, Collection<int, Lesson>>
     */
    private function publishedLessonsByModule(array $moduleIds): Collection
    {
        if ($moduleIds === []) {
            return collect();
        }

        return Lesson::query()
            ->whereIn('module_id', $moduleIds)
            ->where('is_published', true)
            ->with(['topics' => fn ($query) => $query->orderBy('order')])
            ->orderBy('order')
            ->get()
            ->groupBy('module_id');
    }

    /** @param array<int, int> $moduleIds */
    private function enrollmentsFor(User $user, array $moduleIds): Collection
    {
        return $moduleIds === []
            ? collect()
            : ModuleEnrollment::query()->where('user_id', $user->id)->whereIn('module_id', $moduleIds)->get()->keyBy('module_id');
    }

    /** @param array<int, int> $moduleIds */
    private function purchasesFor(User $user, array $moduleIds): Collection
    {
        return $moduleIds === []
            ? collect()
            : ModulePurchase::query()->where('user_id', $user->id)->whereIn('module_id', $moduleIds)
                ->where('status', ModulePurchase::STATUS_COMPLETED)->get()->keyBy('module_id');
    }

    /** @param array<int, int> $moduleIds */
    private function completedProgressFor(User $user, array $moduleIds): Collection
    {
        if ($moduleIds === []) {
            return collect();
        }

        return UserProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('module_id', $moduleIds)
            ->where('completed', true)
            ->get(['module_id', 'lesson_id'])
            ->groupBy('module_id')
            ->map(fn (Collection $progress): Collection => $progress->pluck('lesson_id')->map(fn ($id): int => (int) $id));
    }

    private function progressPercentage(
        bool $approved,
        bool $canonical,
        Collection $instructionalTopics,
        Collection $completedTopicIds,
        Collection $lessonIds,
        Collection $completedLessonIds,
    ): int {
        if (! $approved) {
            return 0;
        }
        if ($canonical) {
            return 100;
        }
        if ($instructionalTopics->isNotEmpty()) {
            $topicIds = $instructionalTopics->pluck('id')->map(fn ($id): int => (int) $id);
            return (int) round(($completedTopicIds->intersect($topicIds)->count() / $topicIds->count()) * 100);
        }
        if ($lessonIds->isNotEmpty()) {
            return (int) round(($completedLessonIds->intersect($lessonIds)->count() / $lessonIds->count()) * 100);
        }

        return 0;
    }

    private function enrollmentStatus(?ModuleEnrollment $enrollment): ?string
    {
        $status = $enrollment?->status;
        return $status instanceof EnrollmentStatus ? $status->value : ($status === null ? null : (string) $status);
    }

    private function isAgeAppropriate($module, ?string $ageBracket): bool
    {
        if ($ageBracket === null) {
            return false;
        }

        return in_array($ageBracket, $module->learnerCategoryKeys(), true);
    }

    private function unavailableReason(?string $status, bool $approved, bool $activeVisible, $module): ?string
    {
        if ($status === EnrollmentStatus::Pending->value) {
            return 'Enrollment is awaiting approval.';
        }
        if ($status === EnrollmentStatus::Rejected->value) {
            return 'Enrollment was rejected.';
        }
        if ($approved && ! $activeVisible) {
            return $module->trashed() || ! $module->isLearnerVisible()
                ? 'This module is no longer available, but its history remains visible.'
                : 'This module is outside your current learner category.';
        }

        return null;
    }

    /** @param array<string, mixed> $presentation */
    private function summary(array $presentation): array
    {
        return [
            'path' => $presentation['path'],
            'progress_percentage' => $presentation['progress_percentage'],
            'completed_modules' => $presentation['completed_modules'],
            'actionable_modules' => $presentation['actionable_modules'],
            'total_modules' => $presentation['total_modules'],
            'completed_path' => $presentation['completed_path'],
            'current' => $presentation['current'],
            'recommended' => $presentation['recommended'],
            'action_url' => $presentation['action_url'],
            'action_label' => $presentation['action_label'],
        ];
    }

    private function routeIfAvailable(string $name, object $parameter): ?string
    {
        return app('router')->has($name) ? route($name, $parameter) : null;
    }
}
