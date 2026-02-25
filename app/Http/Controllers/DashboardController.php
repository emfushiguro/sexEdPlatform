<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Module;
use App\Models\Counselor;
use App\Models\Clinic;
use App\Models\Seminar;
use App\Models\UserProgress;
use App\Models\Payment;
use App\Services\PayMongoPaymentLinkService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Auto-verify any pending subscription against PayMongo API.
        // This fires when the user navigates to the dashboard after paying
        // instead of waiting on the pending page.
        $this->autoVerifyPendingPayment($user);

        $learnerProfile = $user->learnerProfile;

        // Get age and age bracket
        $age = $learnerProfile->getAge();
        $ageBracket = $learnerProfile->getAgeBracket();

        // Get enrolled modules (exclude deleted modules)
        $enrolledModules = $user->moduleEnrollments()
            ->with(['module' => function($query) {
                $query->withCount('lessons');
            }])
            ->latest()
            ->take(6)
            ->get()
            ->filter(function($enrollment) {
                return $enrollment->module !== null;
            });

        // Calculate progress for enrolled modules
        $progressData = [];
        foreach ($enrolledModules as $enrollment) {
            $module = $enrollment->module;
            $totalLessons = $module->lessons_count ?? 0;
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

        // Get recommended modules (published, age-appropriate, not enrolled)
        $recommendedModules = Module::where('is_published', true)
            ->forAge($age)
            ->whereNotIn('id', $enrolledModules->pluck('module_id'))
            ->withCount('lessons')
            ->orderBy('order')
            ->take(6)
            ->get();

        $data = [
            'user' => $user,
            'learnerProfile' => $learnerProfile,
            'age' => $age,
            'ageBracket' => $ageBracket,
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

        // Route to age-appropriate dashboard view
        return match($ageBracket) {
            'kids' => view('dashboards.kids', $data),
            'teens' => view('dashboards.teens', $data),
            'adults' => view('dashboards.adults', $data),
            default => view('dashboards.learner', $data), // fallback
        };
    }

    private function counselorDashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $clinic = $user->clinic;

        $data = [
            'clinic' => $clinic,
            'totalServices' => 0, // Can be expanded later
        ];

        return view('dashboards.clinic', $data);
    }

    private function organizationDashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $organization = $user->organization;

        $data = [
            'organization' => $organization,
            'seminars' => $organization?->seminars()->latest()->take(10)->get() ?? collect(),
            'totalSeminars' => $organization?->seminars()->count() ?? 0,
        ];

        return view('dashboards.organization', $data);
    }

    /**
     * Check if the user has a pending subscription whose PayMongo link is already paid,
     * and activate it automatically. Called from learnerDashboard() as a safety net for
     * users who navigate to the dashboard instead of waiting on the pending page.
     */
    private function autoVerifyPendingPayment($user): void
    {
        try {
            $pendingSubscription = $user->subscriptions()
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$pendingSubscription) {
                return;
            }

            // Layer 1: payment already completed in DB but sub still pending
            $completedPayment = $pendingSubscription->payments()
                ->where('status', 'completed')
                ->first();

            if ($completedPayment) {
                app(SubscriptionService::class)->activate($pendingSubscription);
                session()->flash('success', 'Your subscription is now active! 🎉');
                return;
            }

            // Layer 2: ask PayMongo API if the link is paid
            $pendingPayment = $pendingSubscription->payments()
                ->where('status', 'pending')
                ->whereNotNull('payment_details')
                ->latest()
                ->first();

            if (!$pendingPayment) {
                return;
            }

            $linkId = $pendingPayment->payment_details['paymongo_link_id'] ?? null;
            if (!$linkId) {
                return;
            }

            $paymongoService = app(PayMongoPaymentLinkService::class);
            $response = $paymongoService->retrievePaymentLink($linkId);
            $pmStatus = $response['data']['attributes']['status'] ?? null;

            if ($pmStatus === 'paid') {
                DB::transaction(function () use ($pendingPayment, $pendingSubscription) {
                    $pendingPayment->update([
                        'status'  => 'completed',
                        'paid_at' => now(),
                        'payment_details' => array_merge($pendingPayment->payment_details ?? [], [
                            'verified_via_dashboard' => true,
                            'verified_at'            => now()->toDateTimeString(),
                        ]),
                    ]);
                    app(SubscriptionService::class)->activate($pendingSubscription);
                });
                session()->flash('success', 'Your payment was confirmed! Subscription is now active. 🎉');
            }
        } catch (\Exception $e) {
            // Non-critical — don't break the dashboard if PayMongo is unavailable
            Log::info('Dashboard auto-verify skipped', ['error' => $e->getMessage()]);
        }
    }
}
