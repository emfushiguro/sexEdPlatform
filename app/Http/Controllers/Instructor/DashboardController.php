<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $instructorId = Auth::id();

        // Scoped to instructor's own modules only
        $myModuleIds = Module::where('created_by', $instructorId)->pluck('id');

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
            'moduleStats', 'quizStats', 'instructorModules', 'calendarDates'
        ));
    }
}

