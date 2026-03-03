<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        if (!$learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access your dashboard.');
        }

        // Enrolled modules with progress
        $enrollments = ModuleEnrollment::where('user_id', $user->id)
            ->with(['module' => function ($query) {
                $query->withCount(['lessons' => function ($q) {
                    $q->where('is_published', true);
                }]);
            }])
            ->latest()
            ->get();

        // Calculate per-module progress
        $enrollmentData = $enrollments->map(function ($enrollment) use ($user) {
            $module = $enrollment->module;
            if (!$module) return null;

            $totalLessons = $module->lessons_count;
            $completedLessons = UserProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('completed', true)
                ->count();

            $progressPercent = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0;

            return [
                'enrollment'        => $enrollment,
                'module'            => $module,
                'total_lessons'     => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent'  => $progressPercent,
            ];
        })->filter()->values();

        // Stats
        $totalEnrolled   = $enrollments->count();
        $totalCompleted  = $enrollments->whereNotNull('completed_at')->count();
        $inProgress      = $totalEnrolled - $totalCompleted;

        // "Continue Learning" — most-recently updated in-progress enrollment
        $continueData = $enrollmentData
            ->filter(fn($d) => $d['progress_percent'] > 0 && !$d['enrollment']->completed_at)
            ->sortByDesc(fn($d) => $d['enrollment']->updated_at)
            ->first();

        // Time-based greeting
        $hour = now()->hour;
        $greeting = match(true) {
            $hour < 12  => 'Good morning',
            $hour < 17  => 'Good afternoon',
            default     => 'Good evening',
        };

        return view('learner.dashboard', [
            'learnerProfile'  => $learnerProfile,
            'enrollmentData'  => $enrollmentData,
            'totalEnrolled'   => $totalEnrolled,
            'totalCompleted'  => $totalCompleted,
            'inProgress'      => $inProgress,
            'continueData'    => $continueData,
            'greeting'        => $greeting,
        ]);
    }
}
