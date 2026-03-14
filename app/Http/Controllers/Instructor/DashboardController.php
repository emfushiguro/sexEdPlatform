<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $instructorId = Auth::id();
        $now = now();
        $currentStart = Carbon::now()->subDays(29)->startOfDay();
        $previousStart = (clone $currentStart)->subDays(30);
        $previousEnd = (clone $currentStart)->subSecond();

        // Scoped to instructor's own modules only
        $myModuleIds = Module::where('created_by', $instructorId)->pluck('id');

        $trendData = function (float|int $current, float|int $previous): array {
            if ($previous <= 0 || $current <= 0) {
                return [
                    'direction' => 'flat',
                    'percent' => 0.0,
                    'text' => 'No data',
                ];
            }

            $delta = (($current - $previous) / $previous) * 100;
            $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
            $rounded = round(abs($delta), 1);

            return [
                'direction' => $direction,
                'percent' => $rounded,
                'text' => ($direction === 'up' ? '+' : ($direction === 'down' ? '-' : '')) . $rounded . '%',
            ];
        };

        $stats = [
            'total_learners'      => ModuleEnrollment::whereIn('module_id', $myModuleIds)->distinct('user_id')->count('user_id'),
            'total_modules'       => Module::where('created_by', $instructorId)->count(),
            'published_modules'   => Module::where('created_by', $instructorId)->where('is_published', true)->count(),
            'total_quizzes'       => Quiz::whereIn('module_id', $myModuleIds)->count(),
            'pending_enrollments' => ModuleEnrollment::pending()->whereIn('module_id', $myModuleIds)->count(),
            'enrolled_learners'   => ModuleEnrollment::whereIn('module_id', $myModuleIds)->where('status', 'approved')->count(),
        ];

        // Recent activities — last 10 enrollments across instructor's modules
        $recentActivities = ModuleEnrollment::with(['user', 'module'])
            ->whereIn('module_id', $myModuleIds)
            ->latest()
            ->limit(10)
            ->get();

        // Pending enrollments for dashboard section (limit 5)
        $pendingEnrollments = ModuleEnrollment::pending()
            ->with(['user', 'module'])
            ->whereIn('module_id', $myModuleIds)
            ->latest()
            ->limit(5)
            ->get();

        // Top modules by enrollment (limit 5)
        $moduleStats = Module::withCount('enrollments')
            ->where('created_by', $instructorId)
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();

        // Quiz performance summary (limit 5)
        $quizStats = Quiz::whereIn('module_id', $myModuleIds)
            ->with('module:id,title')
            ->withCount('attempts')
            ->withAvg('attempts', 'score')
            ->limit(5)
            ->get();

        // All instructor modules for carousel
        $instructorModules = Module::where('created_by', $instructorId)
            ->withCount('enrollments')
            ->latest()
            ->get();

        $periodStats = [
            'total_learners_current' => ModuleEnrollment::whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$currentStart, $now])
                ->distinct('user_id')
                ->count('user_id'),
            'total_learners_previous' => ModuleEnrollment::whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->distinct('user_id')
                ->count('user_id'),
            'published_modules_current' => Module::where('created_by', $instructorId)
                ->where('is_published', true)
                ->whereBetween('created_at', [$currentStart, $now])
                ->count(),
            'published_modules_previous' => Module::where('created_by', $instructorId)
                ->where('is_published', true)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count(),
            'quizzes_current' => Quiz::whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$currentStart, $now])
                ->count(),
            'quizzes_previous' => Quiz::whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count(),
            'pending_current' => ModuleEnrollment::pending()
                ->whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$currentStart, $now])
                ->count(),
            'pending_previous' => ModuleEnrollment::pending()
                ->whereIn('module_id', $myModuleIds)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count(),
            'enrolled_current' => ModuleEnrollment::whereIn('module_id', $myModuleIds)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$currentStart, $now])
                ->count(),
            'enrolled_previous' => ModuleEnrollment::whereIn('module_id', $myModuleIds)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count(),
        ];

        $averageQuizScoreAllTime = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->whereIn('quizzes.module_id', $myModuleIds)
            ->avg('quiz_attempts.score');

        $averageQuizScoreLast30Days = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->whereIn('quizzes.module_id', $myModuleIds)
            ->whereBetween('quiz_attempts.created_at', [$currentStart, $now])
            ->avg('quiz_attempts.score');

        $averageQuizScorePrev30Days = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->whereIn('quizzes.module_id', $myModuleIds)
            ->whereBetween('quiz_attempts.created_at', [$previousStart, $previousEnd])
            ->avg('quiz_attempts.score');

        $avgAllTimeRounded = $averageQuizScoreAllTime !== null ? round((float) $averageQuizScoreAllTime, 1) : null;
        $avg30Rounded = $averageQuizScoreLast30Days !== null ? round((float) $averageQuizScoreLast30Days, 1) : null;

        $allTimeTrend = $trendData($avg30Rounded ?? 0, $averageQuizScorePrev30Days !== null ? round((float) $averageQuizScorePrev30Days, 1) : 0);
        $last30Trend = $trendData($avg30Rounded ?? 0, $averageQuizScorePrev30Days !== null ? round((float) $averageQuizScorePrev30Days, 1) : 0);

        $avgQuizScoreScopes = [
            'defaultScope' => 'all_time',
            'all_time' => [
                'value' => $avgAllTimeRounded !== null ? $avgAllTimeRounded . '%' : '--',
                'trendText' => $allTimeTrend['text'],
                'trendClass' => $allTimeTrend['direction'] === 'up'
                    ? 'bg-success-50 text-success-600'
                    : ($allTimeTrend['direction'] === 'down' ? 'bg-error-50 text-error-600' : 'bg-gray-100 text-gray-600'),
            ],
            'last_30_days' => [
                'value' => $avg30Rounded !== null ? $avg30Rounded . '%' : '--',
                'trendText' => $last30Trend['text'],
                'trendClass' => $last30Trend['direction'] === 'up'
                    ? 'bg-success-50 text-success-600'
                    : ($last30Trend['direction'] === 'down' ? 'bg-error-50 text-error-600' : 'bg-gray-100 text-gray-600'),
            ],
        ];

        $statCards = [
            [
                'label' => 'Total Learners',
                'value' => $stats['total_learners'],
                'route' => route('instructor.users.index'),
                'icon' => 'users',
                'trend' => $trendData($periodStats['total_learners_current'], $periodStats['total_learners_previous']),
                'secondaryAction' => [
                    'route' => route('instructor.users.index'),
                    'ariaLabel' => 'View learners',
                ],
            ],
            [
                'label' => 'Modules',
                'value' => $stats['published_modules'] . '/' . $stats['total_modules'],
                'route' => route('instructor.modules.index'),
                'icon' => 'book',
                'trend' => $trendData($periodStats['published_modules_current'], $periodStats['published_modules_previous']),
                'secondaryAction' => [
                    'route' => route('instructor.modules.index'),
                    'ariaLabel' => 'View modules',
                ],
            ],
            [
                'label' => 'Quizzes',
                'value' => $stats['total_quizzes'],
                'route' => route('instructor.quizzes.index'),
                'icon' => 'clipboard',
                'trend' => $trendData($periodStats['quizzes_current'], $periodStats['quizzes_previous']),
                'secondaryAction' => [
                    'route' => route('instructor.quizzes.index'),
                    'ariaLabel' => 'View quizzes',
                ],
            ],
            [
                'label' => 'Pending Requests',
                'value' => $stats['pending_enrollments'],
                'route' => route('instructor.enrollments.index'),
                'icon' => 'clock',
                'trend' => $trendData($periodStats['pending_current'], $periodStats['pending_previous']),
                'secondaryAction' => [
                    'route' => route('instructor.enrollments.index'),
                    'ariaLabel' => 'View pending requests',
                ],
            ],
            [
                'label' => 'Enrolled Learners',
                'value' => $stats['enrolled_learners'],
                'route' => route('instructor.users.index'),
                'icon' => 'check-circle',
                'trend' => $trendData($periodStats['enrolled_current'], $periodStats['enrolled_previous']),
                'secondaryAction' => [
                    'route' => route('instructor.users.index'),
                    'ariaLabel' => 'View enrolled learners',
                ],
            ],
            [
                'label' => 'Average Quiz Score',
                'value' => $avgAllTimeRounded !== null ? $avgAllTimeRounded . '%' : '--',
                'route' => route('instructor.quizzes.index'),
                'icon' => 'chart',
                'trend' => $allTimeTrend,
                'secondaryAction' => [
                    'route' => route('instructor.quizzes.index'),
                    'ariaLabel' => 'View quiz analytics',
                ],
            ],
        ];

        $dashboardHero = [
            'title' => 'Instructor Dashboard',
            'subtitle' => 'Welcome back, ' . (Auth::user()->first_name ?? Auth::user()->name) . '. Monitor your classes and act quickly on pending learner activity.',
            'cta_label' => 'Manage Modules',
            'cta_route' => route('instructor.modules.index'),
        ];

        // Calendar activity dots — enrollment dates this month
        $calendarDates = ModuleEnrollment::whereIn('module_id', $myModuleIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('created_at')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->unique()
            ->values()
            ->toArray();

        return view('instructor.dashboard', compact(
            'stats', 'recentActivities', 'pendingEnrollments',
            'moduleStats', 'quizStats', 'instructorModules', 'calendarDates',
            'statCards', 'avgQuizScoreScopes', 'dashboardHero'
        ));
    }
}

