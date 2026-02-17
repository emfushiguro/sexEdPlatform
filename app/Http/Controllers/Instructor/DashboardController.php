<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $stats = [
            'total_learners' => User::role('learner')->count(),
            'total_modules' => Module::count(),
            'published_modules' => Module::where('is_published', true)->count(),
            'total_quizzes' => Quiz::count(),
            'total_certificates' => Certificate::count(),
            'pending_enrollments' => ModuleEnrollment::pending()->count(),
            'premium_users' => User::role('learner')
                ->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active')
                      ->where('plan', 'premium');
                })
                ->count(),
        ];

        // Recent activity (last 10 certificates issued)
        $recentCertificates = Certificate::with(['user', 'module'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Module enrollment stats
        $moduleStats = Module::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();

        return view('instructor.dashboard', compact('stats', 'recentCertificates', 'moduleStats'));
    }
}
