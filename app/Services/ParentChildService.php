<?php

namespace App\Services;

use App\Models\ModuleEnrollment;
use App\Models\QuizAttempt;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class ParentChildService
{
    /**
     * Get all approved module enrollments for the child with progress data.
     */
    public function getProgress(User $child): Collection
    {
        $enrollments = ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'approved')
            ->with(['module.lessons'])
            ->latest('enrolled_at')
            ->get();

        return $enrollments->map(function (ModuleEnrollment $enrollment) use ($child) {
            $totalLessons     = $enrollment->module->lessons->count();
            $completedLessons = UserProgress::where('user_id', $child->id)
                ->where('module_id', $enrollment->module_id)
                ->where('completed', true)
                ->count();

            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_lessons     = $totalLessons;
            $enrollment->progress_pct      = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0;

            return $enrollment;
        });
    }

    /**
     * Get all quiz attempts for the child, newest first.
     */
    public function getQuizResults(User $child): Collection
    {
        return QuizAttempt::where('user_id', $child->id)
            ->with(['quiz.module'])
            ->latest('completed_at')
            ->get();
    }

    /**
     * Get gamification summary and reward log for the child.
     */
    public function getAchievements(User $child): array
    {
        $gamification = UserGamification::firstOrCreate(
            ['user_id' => $child->id],
            ['level' => 1, 'score' => 0, 'total_points' => 0, 'streak_count' => 0]
        );

        $rewardLogs = RewardLog::where('user_id', $child->id)
            ->with('achievement')
            ->latest('earned_at')
            ->get();

        return [
            'gamification' => $gamification,
            'rewardLogs'   => $rewardLogs,
        ];
    }

    /**
     * Get module enrollments awaiting parent approval.
     */
    public function getPendingEnrollments(User $child): Collection
    {
        return ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'pending_parent_approval')
            ->with('module')
            ->latest()
            ->get();
    }
}
