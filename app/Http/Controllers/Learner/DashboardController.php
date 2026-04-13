<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\LessonTopic;
use App\Models\LessonTopicProgress;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\RewardLog;
use App\Models\UserDailyShield;
use App\Models\UserProgress;
use App\Models\InstructorApplication;
use App\Services\SubscriptionService;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

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

            // Lesson-level count for card display ("X/Y lessons completed")
            $completedLessons = UserProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('completed', true)
                ->count();

            // Topic-based progress — reflects partial lesson progress accurately
            $lessonIds = $module->lessons()->where('is_published', true)->pluck('id');
            $totalTopics = LessonTopic::whereIn('lesson_id', $lessonIds)->count();

            if ($totalTopics > 0) {
                $completedTopics = LessonTopicProgress::where('user_id', $user->id)
                    ->whereIn('lesson_topic_id', function ($q) use ($lessonIds) {
                        $q->select('id')->from('lesson_topics')->whereIn('lesson_id', $lessonIds);
                    })
                    ->where('completed', true)
                    ->count();
                $progressPercent = round(($completedTopics / $totalTopics) * 100);
            } else {
                // No topics: fall back to lesson-level completion
                $progressPercent = $totalLessons > 0
                    ? round(($completedLessons / $totalLessons) * 100)
                    : 0;
            }

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

        // ── Shields today ───────────────────────────────────────────────
        $shieldsRemaining = UserDailyShield::getShields($user);

        // ── Streak data ─────────────────────────────────────────────────
        $streakActiveDays = LessonTopicProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->whereBetween('completed_at', [
                now()->startOfWeek(0),
                now()->endOfWeek(6),
            ])
            ->get()
            ->map(fn($p) => (int) $p->completed_at->dayOfWeek)
            ->unique()
            ->values()
            ->toArray();
        $longestStreak = $gamification?->longest_streak ?? 0;
        $streakSavers  = $gamification?->streak_savers ?? 0;

        // ── Recent achievements ─────────────────────────────────────────
        $recentAchievements = RewardLog::where('user_id', $user->id)
            ->with('achievement')
            ->orderByDesc('earned_at')
            ->take(3)
            ->get()
            ->pluck('achievement')
            ->filter();

        // ── Greeting ────────────────────────────────────────────────────
        $hour     = now('Asia/Manila')->hour;
        $greeting = match (true) {
            $hour < 12  => 'Good Morning',
            $hour < 17  => 'Good Afternoon',
            default     => 'Good Evening',
        };

        // ── Profile modal data ───────────────────────────────────────────
        $currentSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();

        $currentPlan = $currentSubscription && $currentSubscription->plan_id
            ? \App\Models\SubscriptionPlan::find($currentSubscription->plan_id)
            : null;

        $usernameCooldownDays = 0;
        $isPremium = $this->subscriptionService->isUserPremium($user);
        $hasUnlimitedUsernameChanges = $this->subscriptionService->hasFeature(
            $user,
            SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE
        );
        $hasUnlimitedQuizShields = $this->subscriptionService->hasFeature(
            $user,
            SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS
        );

        if (!$hasUnlimitedUsernameChanges && $learnerProfile->username_changed_at) {
            $daysSince = now()->diffInDays($learnerProfile->username_changed_at);
            $usernameCooldownDays = $daysSince < 7 ? (7 - (int) $daysSince) : 0;
        }

        $profileEntitlementHints = [
            [
                'key' => 'username_change',
                'label' => 'Username changes',
                'value' => $hasUnlimitedUsernameChanges ? 'Unlimited' : 'Every 7 days',
                'description' => $hasUnlimitedUsernameChanges
                    ? 'Your current plan removes the username cooldown.'
                    : 'Free baseline applies a 7-day cooldown between username changes.',
                'is_enabled' => $hasUnlimitedUsernameChanges,
            ],
            [
                'key' => 'quiz_shields',
                'label' => 'Quiz shields',
                'value' => $hasUnlimitedQuizShields ? 'Unlimited' : '3 per day',
                'description' => $hasUnlimitedQuizShields
                    ? 'Retry quizzes without consuming daily shields.'
                    : 'Free baseline includes 3 shields each day, then resets at midnight.',
                'is_enabled' => $hasUnlimitedQuizShields,
            ],
        ];

        $latestInstructorApplication = InstructorApplication::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        $hasPendingInstructorApplication = $latestInstructorApplication?->status === 'pending';
        $canApplyAsInstructor = $user->isLearner()
            && ! $user->isParentRegistration()
            && ! $user->children()->exists()
            && ! $hasPendingInstructorApplication;

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
            'shieldsRemaining',
            'streakActiveDays',
            'longestStreak',
            'streakSavers',
            'recentAchievements',
            'greeting',
            'currentSubscription',
            'currentPlan',
            'isPremium',
            'usernameCooldownDays',
            'hasUnlimitedQuizShields',
            'profileEntitlementHints',
            'latestInstructorApplication',
            'hasPendingInstructorApplication',
            'canApplyAsInstructor',
        ));
    }
}

