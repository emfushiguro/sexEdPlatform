<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessModulePaymentRequest;
use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ModulePurchase;
use App\Models\ParentChildAccount;
use App\Models\QuizAttempt;
use App\Models\LessonTopicProgress;
use App\Models\UserDailyShield;
use App\Models\UserProgress;
use App\Services\ModulePurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModulePurchaseService $modulePurchaseService,
    ) {
    }

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
                    $inner->learnerVisible()->forAge($learnerAge);
                });

                if ($enrolledModuleIds->isNotEmpty()) {
                    $query->orWhereIn('id', $enrolledModuleIds);
                }
            })
            ->withCount([
                'lessons',
                'enrollments as approved_enrollments_count' => fn ($query) => $query->where('status', EnrollmentStatus::Approved),
            ])
            ->with([
                'creator.instructorProfile',
                'purchases' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', ModulePurchase::STATUS_COMPLETED),
            ])
            ->with(['lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        $modules->each(function (Module $module) {
            $module->applyPublishedSnapshot();
        });

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
        if (!$module->isLearnerVisible() && !$isApprovedEnrollment) {
            abort(404);
        }

        $module->loadMissing('publishedRevision');
        $module->applyPublishedSnapshot();
        $module->loadMissing('creator.instructorProfile');

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

        $isPaidModule = $module->isPaidAccess();
        $modulePurchase = $this->modulePurchaseService->getCompletedPurchase($user, $module);
        $hasPurchased = $modulePurchase !== null;

        $approvedEnrollmentsCount = ModuleEnrollment::query()
            ->approvedForModule($module->id)
            ->count();

        $isAtCapacity = $module->enrollment_limit !== null
            && $approvedEnrollmentsCount >= (int) $module->enrollment_limit;

        $needsParentApproval = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->where('can_approve_content', true)
            ->where('verification_status', 'approved')
            ->exists();

        $isParentApprovedForPurchase = !$needsParentApproval
            || ($enrollment && in_array($enrollment->status, [EnrollmentStatus::Pending, EnrollmentStatus::Approved], true));

        $canPurchase = $isPaidModule
            && !$hasPurchased
            && !$isAtCapacity
            && $isParentApprovedForPurchase
            && (!$enrollment || $enrollment->status !== EnrollmentStatus::Rejected);

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
            'isPaidModule',
            'modulePurchase',
            'hasPurchased',
            'approvedEnrollmentsCount',
            'isAtCapacity',
            'needsParentApproval',
            'isParentApprovedForPurchase',
            'canPurchase',
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

        $module->loadMissing('publishedRevision');
        $module->applyPublishedSnapshot();

        if (!$user->learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        if ($module->isPaidAccess() && !$this->modulePurchaseService->hasCompletedPurchase($user, $module)) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'This module requires payment before enrollment.');
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

    public function purchase(Module $module)
    {
        $user = Auth::user();

        if (!$user->learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        if (!$module->isLearnerVisible()) {
            abort(404);
        }

        $module->loadMissing('publishedRevision');
        $module->applyPublishedSnapshot();

        if (!$module->isPaidAccess()) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'This module does not require payment.');
        }

        $learnerAge = $user->learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        if ($this->modulePurchaseService->hasCompletedPurchase($user, $module)) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'You have already purchased this module.');
        }

        $existingEnrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->first();

        $needsParentApproval = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->where('can_approve_content', true)
            ->where('verification_status', 'approved')
            ->exists();

        if ($needsParentApproval) {
            if ($existingEnrollment?->status === EnrollmentStatus::PendingParentApproval) {
                return redirect()->route('learner.modules.show', $module)
                    ->with('info', 'Waiting for parent approval before payment.');
            }

            if ($existingEnrollment?->status === EnrollmentStatus::Rejected) {
                return redirect()->route('learner.modules.show', $module)
                    ->with('error', 'Parent approval was not granted for this module.');
            }

            if (!$existingEnrollment) {
                ModuleEnrollment::query()->create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'status' => EnrollmentStatus::PendingParentApproval,
                    'enrolled_at' => null,
                ]);

                return redirect()->route('learner.modules.show', $module)
                    ->with('info', 'Request sent for parent approval. Complete payment after approval.');
            }
        }

        $isAtCapacity = $module->enrollment_limit !== null
            && ModuleEnrollment::query()->approvedForModule($module->id)->count() >= (int) $module->enrollment_limit;

        if ($isAtCapacity) {
            return redirect()->route('learner.modules.show', $module)
                ->with('error', 'Enrollment Closed: this module is already full.');
        }

        return redirect()->route('learner.modules.purchase.form', $module);
    }

    public function purchaseForm(Module $module)
    {
        $user = Auth::user();

        if (!$user->learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        if (!$module->isLearnerVisible()) {
            abort(404);
        }

        $module->loadMissing('publishedRevision');
        $module->applyPublishedSnapshot();

        if (!$module->isPaidAccess()) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'This module does not require payment.');
        }

        $learnerAge = $user->learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        if ($this->modulePurchaseService->hasCompletedPurchase($user, $module)) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'You have already purchased this module.');
        }

        $existingEnrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->first();

        $needsParentApproval = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->where('can_approve_content', true)
            ->where('verification_status', 'approved')
            ->exists();

        $isParentApprovedForPurchase = !$needsParentApproval
            || ($existingEnrollment && in_array($existingEnrollment->status, [EnrollmentStatus::Pending, EnrollmentStatus::Approved], true));

        if ($needsParentApproval && !$isParentApprovedForPurchase) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'Parent approval is required before payment.');
        }

        $isAtCapacity = $module->enrollment_limit !== null
            && ModuleEnrollment::query()->approvedForModule($module->id)->count() >= (int) $module->enrollment_limit;

        if ($isAtCapacity) {
            return redirect()->route('learner.modules.show', $module)
                ->with('error', 'Enrollment Closed: this module is already full.');
        }

        $secretKey = (string) config('paymongo.secret_key', '');
        $paymongoMode = str_starts_with($secretKey, 'sk_test_')
            ? 'sandbox'
            : (str_starts_with($secretKey, 'sk_live_') ? 'live' : 'unknown');

        $amount = (float) ($module->price_amount ?? 0);

        return view('payments.module-create', [
            'module' => $module,
            'amount' => $amount,
            'paymongoMode' => $paymongoMode,
        ]);
    }

    public function processPurchase(ProcessModulePaymentRequest $request, Module $module)
    {
        $user = Auth::user();

        if (!$user->learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        if (!$module->isLearnerVisible()) {
            abort(404);
        }

        $module->loadMissing('publishedRevision');
        $module->applyPublishedSnapshot();

        if (!$module->isPaidAccess()) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'This module does not require payment.');
        }

        $learnerAge = $user->learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        if ($this->modulePurchaseService->hasCompletedPurchase($user, $module)) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'You have already purchased this module.');
        }

        $existingEnrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->first();

        $needsParentApproval = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->where('can_approve_content', true)
            ->where('verification_status', 'approved')
            ->exists();

        $isParentApprovedForPurchase = !$needsParentApproval
            || ($existingEnrollment && in_array($existingEnrollment->status, [EnrollmentStatus::Pending, EnrollmentStatus::Approved], true));

        if ($needsParentApproval && !$isParentApprovedForPurchase) {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', 'Parent approval is required before payment.');
        }

        $isAtCapacity = $module->enrollment_limit !== null
            && ModuleEnrollment::query()->approvedForModule($module->id)->count() >= (int) $module->enrollment_limit;

        if ($isAtCapacity) {
            return redirect()->route('learner.modules.show', $module)
                ->with('error', 'Enrollment Closed: this module is already full.');
        }

        $checkout = $this->modulePurchaseService->createCheckout(
            $user,
            $module,
            (string) $request->string('payment_method'),
            [
                'name' => (string) $request->string('billing_name'),
                'email' => (string) $request->string('billing_email'),
                'phone' => (string) $request->string('billing_phone'),
            ]
        );

        if (($checkout['status'] ?? null) !== 'checkout_created') {
            return redirect()->route('learner.modules.show', $module)
                ->with('info', $checkout['message'] ?? 'Unable to start checkout right now.');
        }

        $paymentId = (int) ($checkout['payment_id'] ?? 0);
        if ($paymentId > 0) {
            return redirect()->route('payment.pending', ['payment' => $paymentId])
                ->with('paymongo_checkout_url', (string) ($checkout['checkout_url'] ?? ''));
        }

        return redirect()->away((string) ($checkout['checkout_url'] ?? route('learner.modules.show', $module)));
    }

    public function purchaseSuccess(Module $module)
    {
        $user = Auth::user();

        $payment = $user->payments()
            ->where('status', \App\Enums\PaymentStatus::Pending)
            ->where('payment_details->payment_scope', 'module_purchase')
            ->where('payment_details->module_id', $module->id)
            ->latest('id')
            ->first();

        if (!$payment) {
            return redirect()->route('learner.modules.show', $module)
                ->with('success', 'Payment completed. Access will unlock once confirmation is received.');
        }

        $completed = $this->modulePurchaseService->verifyAndCompletePendingPayment($payment);

        return redirect()->route('learner.modules.show', $module)
            ->with($completed ? 'success' : 'info', $completed
                ? 'Payment confirmed. You now have access to this module.'
                : 'Payment is still being confirmed. Please refresh in a moment.');
    }

    public function purchaseFailed(Module $module)
    {
        return redirect()->route('learner.modules.show', $module)
            ->with('error', 'Payment was cancelled or failed. Please try again.');
    }

    /**
     * Check if learner can access module based on age
     */
    private function canAccessModule(Module $module, int $learnerAge): bool
    {
        return $module->isAppropriateForAge($learnerAge);
    }
}
