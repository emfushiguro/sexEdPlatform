<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\QuizDailyLimit;
use App\Models\RewardLog;
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

        // ── Enrolled modules with progress ──────────────────────────────
        $enrollments = ModuleEnrollment::where('user_id', $user->id)
            ->where('status', 'approved')
            ->with(['module' => function ($query) {
                $query->withCount(['lessons' => function ($q) {
                    $q->where('is_published', true);
                }]);
            }])
            ->latest()
            ->get();

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

            // Find the first incomplete lesson for "Continue Learning"
            $nextLesson = $module->lessons()
                ->where('is_published', true)
                ->whereDoesntHave('userProgress', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('completed', true);
                })
                ->orderBy('order')
                ->first();

            return [
                'enrollment'        => $enrollment,
                'module'            => $module,
                'total_lessons'     => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent'  => $progressPercent,
                'next_lesson'       => $nextLesson,
            ];
        })->filter()->values();

        // ── Recommended modules ─────────────────────────────────────────
        $enrolledModuleIds = $enrollments->pluck('module_id')->toArray();
        $learnerAge = $learnerProfile->getAge();

        $recommendedModules = Module::published()
            ->forAge($learnerAge)
            ->whereNotIn('id', $enrolledModuleIds)
            ->withCount(['lessons' => fn($q) => $q->where('is_published', true)])
            ->orderBy('order')
            ->limit(6)
            ->get();

        // ── Stats ───────────────────────────────────────────────────────
        $totalEnrolled  = $enrollments->count();
        $totalCompleted = $enrollments->whereNotNull('completed_at')->count();
        $inProgress     = $totalEnrolled - $totalCompleted;

        // ── Gamification ────────────────────────────────────────────────
        $gamification = $user->gamification;
        $xpInLevel    = $gamification ? ($gamification->score % 100) : 0;
        $xpToNext     = 100 - $xpInLevel;
        $xpPercent    = $xpInLevel; // out of 100

        // ── Quiz attempts today ─────────────────────────────────────────
        $quizAttemptsUsed      = (int) QuizDailyLimit::where('user_id', $user->id)
            ->where('date', today())
            ->sum('attempts');
        $maxQuizAttempts       = QuizDailyLimit::MAX_FREE_ATTEMPTS;
        $quizAttemptsRemaining = $user->isPremium()
            ? $maxQuizAttempts
            : max(0, $maxQuizAttempts - $quizAttemptsUsed);

        // ── Recent achievements ─────────────────────────────────────────
        $recentAchievements = RewardLog::where('user_id', $user->id)
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->take(3)
            ->get()
            ->pluck('achievement')
            ->filter();

        // ── Greeting ────────────────────────────────────────────────────
        $hour     = now()->hour;
        $greeting = match (true) {
            $hour < 12  => 'Good morning',
            $hour < 17  => 'Good afternoon',
            default     => 'Good evening',
        };

        return view('learner.dashboard', compact(
            'learnerProfile',
            'enrollmentData',
            'totalEnrolled',
            'totalCompleted',
            'inProgress',
            'recommendedModules',
            'gamification',
            'xpInLevel',
            'xpToNext',
            'xpPercent',
            'quizAttemptsUsed',
            'quizAttemptsRemaining',
            'maxQuizAttempts',
            'recentAchievements',
            'greeting',
        ));
    }
}

