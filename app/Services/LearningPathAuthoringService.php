<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LearningPath;
use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningPathAuthoringService
{
    public function save(?LearningPath $path, array $attributes, User $actor): LearningPath
    {
        return DB::transaction(function () use ($path, $attributes, $actor): LearningPath {
            $moduleIds = array_values(array_map('intval', $attributes['module_ids'] ?? []));

            if (count($moduleIds) !== count(array_unique($moduleIds))) {
                throw ValidationException::withMessages([
                    'module_ids' => 'Each module may appear only once in a learning path.',
                ]);
            }

            $rawCategories = (array) ($attributes['categories'] ?? []);
            $invalidCategories = collect($rawCategories)
                ->filter(fn (mixed $category): bool => ! is_string($category)
                    || ! in_array(strtolower(trim($category)), LearnerAgePolicy::activeCategories(), true));
            if ($invalidCategories->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'categories' => 'Choose only the active learner categories: Teenager or Adult.',
                ]);
            }

            $categories = collect($rawCategories)
                ->map(fn (mixed $category): ?string => is_string($category)
                    ? LearnerAgePolicy::normalizeActiveCategory($category)
                    : null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($categories === []) {
                throw ValidationException::withMessages([
                    'categories' => 'Choose at least one active learner category: Teenager or Adult.',
                ]);
            }

            $path ??= new LearningPath(['created_by' => $actor->id]);
            $path->fill(Arr::only($attributes, ['title', 'description', 'thumbnail', 'status']))->save();

            $path->learnerCategories()->delete();
            $path->learnerCategories()->createMany(
                collect($categories)->map(fn (string $category) => ['category' => $category])->all()
            );

            if ($moduleIds === []) {
                $path->pathModules()->delete();
            } else {
                $path->pathModules()->whereNotIn('module_id', $moduleIds)->delete();
            }

            $maxPosition = (int) $path->pathModules()->max('position');
            $offset = $maxPosition + count($moduleIds) + 1;
            $path->pathModules()->increment('position', $offset);

            foreach ($moduleIds as $index => $moduleId) {
                $path->pathModules()->updateOrCreate(
                    ['module_id' => $moduleId],
                    ['position' => $index + 1]
                );
            }

            return $path->fresh(['creator', 'learnerCategories', 'pathModules.module']);
        });
    }
}
