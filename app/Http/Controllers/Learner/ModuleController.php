<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\QuizAttempt;
use App\Models\LessonTopicProgress;
use App\Models\UserDailyShield;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Display all published modules filtered by learner's age
     */
    public function index()
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;
        
        if (!$learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        // Get learner's age
        $learnerAge = $learnerProfile->getAge();
        
        $enrolledModuleIds = $user->moduleEnrollments()
            ->where('status', EnrollmentStatus::Approved)
            ->pluck('module_id');

        // Show active age-appropriate modules plus enrolled modules (including deactivated) for history visibility.
        $modules = Module::where(function ($query) use ($learnerAge, $enrolledModuleIds) {
                $query->where(function ($inner) use ($learnerAge) {
                    $inner->published()->forAge($learnerAge);
                });

                if ($enrolledModuleIds->isNotEmpty()) {
                    $query->orWhereIn('id', $enrolledModuleIds);
                }
            })
            ->withCount('lessons')
            ->with(['lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Get user's enrollments
        $enrollments = $user->moduleEnrollments()->get()->keyBy('module_id');
        $enrolledModuleIds = $enrollments->keys()->toArray();

        // Calculate progress for each module
        $progress = [];
        foreach ($modules as $module) {
            $totalLessons = $module->lessons->count();
            $completedLessons = UserProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('completed', true)
                ->count();
            
            if ($totalLessons > 0) {
                $progressPercentage = ($completedLessons / $totalLessons) * 100;
            } else {
                $progressPercentage = 0;
            }
            
            $progress[$module->id] = (object)[
                'progress_percentage' => round($progressPercentage),
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
            ];
        }

        return view('learner.modules.index', compact('modules', 'enrolledModuleIds', 'enrollments', 'progress'));
    }

    /**
     * Display a specific module with its lessons
     */
    public function show(Module $module)
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        if (!$learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        $enrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->first();

        $isApprovedEnrollment = $enrollment && $enrollment->status === EnrollmentStatus::Approved;

        // Deactivated modules remain viewable for enrolled learners only.
        if (!$module->is_published && !$isApprovedEnrollment) {
            abort(404);
        }

        // Security: Check age-based access for non-enrolled learners.
        $learnerAge = $learnerProfile->getAge();
        if (!$isApprovedEnrollment && !$this->canAccessModule($module, $learnerAge)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        // Get published lessons only
        $lessons = $module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->with([
                'topics' => fn($query) => $query->ordered(),
                'quiz' => fn($query) => $query->where('is_active', true)->with('questions'),
            ])
            ->get();

        // Check enrollment status
        $isEnrolled = $isApprovedEnrollment;
        $enrollmentStatus = $enrollment?->status?->value;

        // Calculate progress
        $totalLessons = $lessons->count();
        $completedLessons = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->count();
        
        $progressPercentage = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
        
        $progress = (object)[
            'progress_percentage' => round($progressPercentage),
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ];

        // Get completed lesson IDs for UI
        $completedLessonIds = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->toArray();

        // Get quizzes for this module
        $moduleQuizzes = $module->quizzes()
            ->where('is_active', true)
            ->with('questions')
            ->get();
        
        // Get lesson quizzes
        $lessonQuizzes = [];
        foreach ($lessons as $lesson) {
            if ($lesson->quiz) {
                $lessonQuizzes[$lesson->id] = $lesson->quiz;
            }
        }
        
        // Get user's quiz attempts
        $quizAttempts = $user->quizAttempts()
            ->whereIn('quiz_id', $moduleQuizzes->pluck('id')->merge(collect($lessonQuizzes)->pluck('id')))
            ->get()
            ->groupBy('quiz_id');

        $moduleCertificate = $user->certificates()->where('module_id', $module->id)->first();

        $topicIds = $lessons->flatMap(fn ($lesson) => $lesson->topics->pluck('id'))->unique();
        $completedTopicIds = LessonTopicProgress::where('user_id', $user->id)
            ->whereIn('lesson_topic_id', $topicIds)
            ->where('completed', true)
            ->pluck('lesson_topic_id')
            ->unique();
        $allTopicsCompleted = $topicIds->isEmpty() || $completedTopicIds->count() === $topicIds->count();

        $lessonQuizIds = collect($lessonQuizzes)->pluck('id')->unique();
        $passedLessonQuizIds = QuizAttempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $lessonQuizIds)
            ->where('passed', true)
            ->pluck('quiz_id')
            ->unique();
        $allLessonQuizzesPassed = $lessonQuizIds->isEmpty() || $passedLessonQuizIds->count() === $lessonQuizIds->count();

        $finalQuizPassed = true;
        if ($module->final_quiz_id) {
            $bestFinalAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $module->final_quiz_id)
                ->orderByDesc('score')
                ->first();

            $finalQuizPassed = $bestFinalAttempt && $bestFinalAttempt->score >= $module->certificate_pass_score;
        }

        $certificateEligible = $isEnrolled
            && $totalLessons > 0
            && count($completedLessonIds) === $totalLessons
            && $allTopicsCompleted
            && $allLessonQuizzesPassed
            && $finalQuizPassed;

        $shieldsRemaining = UserDailyShield::getShields($user);

        return view('learner.modules.show', compact(
            'module', 
            'lessons', 
            'isEnrolled', 
            'enrollmentStatus',
            'progress', 
            'completedLessonIds',
            'moduleQuizzes',
            'lessonQuizzes',
            'quizAttempts',
            'moduleCertificate',
            'certificateEligible',
            'shieldsRemaining'
        ));
    }

    /**
     * Enroll in a module
     */
    public function enroll(Module $module)
    {
        $user = Auth::user();

        if (!$user->learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        // Security checks
        if (!$module->is_published) {
            abort(404);
        }

        // Check age-based access
        $learnerAge = $user->learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return back()->with('error', 'This module is not available for your age group.');
        }

        // Check if already enrolled or has pending request
        $existingEnrollment = $user->moduleEnrollments()->where('module_id', $module->id)->first();
        
        if ($existingEnrollment) {
            if ($existingEnrollment->status === EnrollmentStatus::Pending) {
                return back()->with('info', 'Your enrollment request is pending instructor approval.');
            }
            if ($existingEnrollment->status === EnrollmentStatus::Approved) {
                return back()->with('info', 'You are already enrolled in this module.');
            }
            if ($existingEnrollment->status === EnrollmentStatus::PendingParentApproval) {
                return back()->with('info', 'Your enrollment request is awaiting parental approval.');
            }
            if ($existingEnrollment->status === EnrollmentStatus::Rejected) {
                return back()->with('error', 'Your enrollment request was rejected by the instructor.');
            }
        }

        // Check if parent approval is required
        $needsParentApproval = ParentChildAccount::where('child_user_id', $user->id)
            ->where('can_approve_content', true)
            ->exists();

        if ($needsParentApproval) {
            ModuleEnrollment::create([
                'user_id'     => $user->id,
                'module_id'   => $module->id,
                'status'      => EnrollmentStatus::PendingParentApproval,
                'enrolled_at' => null,
            ]);

            return redirect()->route('learner.modules.show', $module)
                ->with('success', 'Enrollment request submitted! Waiting for parental approval.');
        }

        $isAtCapacity = $module->enrollment_limit !== null
            && ModuleEnrollment::query()
                ->approvedForModule($module->id)
                ->count() >= (int) $module->enrollment_limit;

        if ($isAtCapacity) {
            ModuleEnrollment::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'status' => EnrollmentStatus::Pending,
                'enrolled_at' => null,
            ]);

            return redirect()->route('learner.modules.show', $module)
                ->with('success', 'Module capacity has been reached. Your enrollment is queued for manual review.');
        }

        // Check enrollment mode
        if ($module->enrollment_mode === 'manual') {
            // Manual approval - create pending enrollment
            ModuleEnrollment::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'status' => EnrollmentStatus::Pending,
                'enrolled_at' => null,
            ]);

            return redirect()->route('learner.modules.show', $module)
                ->with('success', 'Enrollment request submitted! Waiting for instructor approval.');
        }

        // Auto approval - create approved enrollment
        ModuleEnrollment::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        return redirect()->route('learner.modules.show', $module)
            ->with('success', 'Successfully enrolled in module!');
    }

    /**
     * Check if learner can access module based on age
     */
    private function canAccessModule(Module $module, int $learnerAge): bool
    {
        return $module->isAppropriateForAge($learnerAge);
    }
}
