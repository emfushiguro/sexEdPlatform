<?php

namespace App\Services\Content;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use Illuminate\Pagination\LengthAwarePaginator;

class ContentAccessService
{
    public function paginateInstructorModules(int $userId, string $status = 'all', int $perPage = 12): LengthAwarePaginator
    {
        if ($status === 'archived') {
            $query = Module::onlyTrashed()->where('created_by', $userId);
        } else {
            $query = Module::query()->where('created_by', $userId);

            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $modules = $query
            ->withCount([
                'lessons',
                'quizzes',
                'enrollments as enrolled_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->latest()
            ->paginate($perPage);

        return $this->appendLegacyLessonQuizCounts($modules);
    }

    public function paginateAdminModules(
        string $scope = 'all',
        string $status = 'all',
        ?string $search = null,
        string $ownerType = 'all',
        int $perPage = 12,
    ): LengthAwarePaginator
    {
        if ($status === 'archived') {
            $query = Module::onlyTrashed();
        } else {
            $query = Module::query();

            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            } elseif ($status === 'pending') {
                $query->where('is_published', false)
                    ->whereIn('current_review_status', ['submitted', 'in_review', 'needs_revision']);
            }
        }

        if ($scope === 'platform') {
            $query->where('content_owner_type', 'admin');
        } elseif ($scope === 'instructor') {
            $query->where('content_owner_type', 'instructor');
        }

        if ($ownerType === 'platform') {
            $query->where('content_owner_type', 'admin');
        } elseif ($ownerType === 'instructor') {
            $query->where('content_owner_type', 'instructor');
        }

        if (is_string($search) && trim($search) !== '') {
            $query->where('title', 'like', '%' . trim($search) . '%');
        }

        $modules = $query
            ->with(['creator.instructorProfile'])
            ->withCount([
                'lessons',
                'quizzes',
                'enrollments as enrolled_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->latest()
            ->paginate($perPage);

        return $this->appendLegacyLessonQuizCounts($modules);
    }

    public function pendingEnrollmentCountForAdmin(): int
    {
        return ModuleEnrollment::query()
            ->where('status', 'pending')
            ->count();
    }

    private function appendLegacyLessonQuizCounts(LengthAwarePaginator $modules): LengthAwarePaginator
    {
        $moduleIds = $modules->getCollection()->pluck('id');

        if ($moduleIds->isNotEmpty()) {
            $directQuizCounts = Quiz::query()
                ->whereIn('module_id', $moduleIds)
                ->selectRaw('module_id, COUNT(*) as total')
                ->groupBy('module_id')
                ->pluck('total', 'module_id');

            $lessonQuizCounts = Quiz::query()
                ->join('lessons', 'lessons.id', '=', 'quizzes.lesson_id')
                ->whereNull('quizzes.module_id')
                ->whereIn('lessons.module_id', $moduleIds)
                ->selectRaw('lessons.module_id as module_id, COUNT(*) as total')
                ->groupBy('lessons.module_id')
                ->pluck('total', 'module_id');

            $modules->setCollection(
                $modules->getCollection()->map(function (Module $module) use ($directQuizCounts, $lessonQuizCounts) {
                    $module->quizzes_count = (int) ($directQuizCounts[$module->id] ?? 0)
                        + (int) ($lessonQuizCounts[$module->id] ?? 0);

                    return $module;
                })
            );
        }

        return $modules;
    }

    public function pendingEnrollmentCountForInstructor(int $userId): int
    {
        return ModuleEnrollment::query()
            ->where('status', 'pending')
            ->whereHas('module', fn ($query) => $query->where('created_by', $userId))
            ->count();
    }
}
