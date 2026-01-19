<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Module;
use App\Models\Counselor;
use App\Models\Clinic;
use App\Models\Seminar;
use App\Models\UserProgress;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Route to role-specific dashboard
        return match($user->role) {
            'admin' => $this->adminDashboard(),
            'counselor' => $this->counselorDashboard(),
            'clinic' => $this->clinicDashboard(),
            'organization' => $this->organizationDashboard(),
            default => $this->learnerDashboard(),
        };
    }

    private function adminDashboard()
    {
        $data = [
            'totalUsers' => User::count(),
            'totalLearners' => User::where('role', 'learner')->count(),
            'totalModules' => Module::count(),
            'pendingCounselors' => Counselor::pending()->count(),
            'pendingClinics' => Clinic::pending()->count(),
            'recentUsers' => User::latest()->take(10)->get(),
        ];

        return view('dashboards.admin', $data);
    }

    private function learnerDashboard()
    {
        $user = auth()->user();
        $learnerProfile = $user->learnerProfile;

        // age_range already contains the grade level
        $gradeLevel = $learnerProfile->age_range;

        // Get enrolled modules
        $enrolledModules = $user->moduleEnrollments()
            ->with(['module' => function($query) {
                $query->withCount('lessons');
            }])
            ->latest()
            ->take(6)
            ->get();

        // Calculate progress for enrolled modules
        $progressData = [];
        foreach ($enrolledModules as $enrollment) {
            $module = $enrollment->module;
            $totalLessons = $module->lessons_count;
            $completedLessons = UserProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('completed', true)
                ->count();
            
            $progressPercentage = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
            
            $progressData[$module->id] = (object)[
                'progress_percentage' => round($progressPercentage),
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
            ];
        }

        // Get recommended modules (published, grade-appropriate, not enrolled)
        $recommendedModules = Module::where('is_published', true)
            ->forGradeLevel($gradeLevel)
            ->whereNotIn('id', $enrolledModules->pluck('module_id'))
            ->withCount('lessons')
            ->orderBy('order')
            ->take(6)
            ->get();

        $data = [
            'user' => $user,
            'learnerProfile' => $learnerProfile,
            'enrolledModules' => $enrolledModules,
            'progressData' => $progressData,
            'recommendedModules' => $recommendedModules,
            'totalEnrolled' => $enrolledModules->count(),
            'subscription' => $user->subscription,
            'isPremium' => $user->isPremium(),
            'certificates' => $user->certificates()->latest()->take(3)->get(),
            'gamification' => $user->gamification,
            'upcomingSeminars' => Seminar::where('schedule', '>', now())->latest()->take(5)->get(),
        ];

        return view('dashboards.learner', $data);
    }

    private function counselorDashboard()
    {
        $user = auth()->user();
        $counselor = $user->counselor;

        $data = [
            'counselor' => $counselor,
            'consultations' => $counselor?->consultations()->latest()->take(10)->get() ?? collect(),
            'totalConsultations' => $counselor?->consultations()->count() ?? 0,
        ];

        return view('dashboards.counselor', $data);
    }

    private function clinicDashboard()
    {
        $user = auth()->user();
        $clinic = $user->clinic;

        $data = [
            'clinic' => $clinic,
            'totalServices' => 0, // Can be expanded later
        ];

        return view('dashboards.clinic', $data);
    }

    private function organizationDashboard()
    {
        $user = auth()->user();
        $organization = $user->organization;

        $data = [
            'organization' => $organization,
            'seminars' => $organization?->seminars()->latest()->take(10)->get() ?? collect(),
            'totalSeminars' => $organization?->seminars()->count() ?? 0,
        ];

        return view('dashboards.organization', $data);
    }
}
