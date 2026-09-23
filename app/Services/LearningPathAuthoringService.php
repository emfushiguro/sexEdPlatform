<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LearningPath;
use App\Models\User;
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

            $path ??= new LearningPath(['created_by' => $actor->id]);
            $path->fill(Arr::only($attributes, ['title', 'description', 'thumbnail', 'status']))->save();

            $path->learnerCategories()->delete();
            $path->learnerCategories()->createMany(
                collect($attributes['categories'])->map(fn (string $category) => ['category' => $category])->all()
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
