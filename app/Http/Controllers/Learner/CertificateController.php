<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\Certificate;
use App\Models\LessonTopicProgress;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use App\Notifications\Instructor\LearnerCertificateIssuedNotification;
use App\Notifications\Learner\CertificateIssuedNotification;
use App\Services\CertificatePdfService;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    /**
     * Display user's certificates
     */
    public function index()
    {
        $user = Auth::user();
        $certificates = $user->certificates()->with('module')->latest()->get();

        return view('learner.certificates.index', compact('certificates'));
    }

    /**
     * Check if user can generate certificate for a module
     */
    public function check(Module $module)
    {
        $user = Auth::user();

        // Check if already has certificate
        if (Certificate::where('user_id', $user->id)->where('module_id', $module->id)->exists()) {
            return back()->with('info', 'You already have a certificate for this module.');
        }

        $eligibilityError = $this->getEligibilityError($user->id, $module);

        if ($eligibilityError) {
            return back()->with('error', $eligibilityError);
        }

        // Generate certificate
        return $this->generate($module);
    }

    /**
     * Generate certificate
     */
    private function generate(Module $module)
    {
        $user = Auth::user();

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => $user->name,
            'module_title_snapshot' => $module->title,
            'issued_at' => now(),
        ]);

        $certificate->loadMissing(['module', 'user']);

        $user->notify(new CertificateIssuedNotification($certificate));

        $moduleInstructor = $module->creator;
        if ($moduleInstructor && (int) $moduleInstructor->id !== (int) $user->id) {
            $moduleInstructor->notify(new LearnerCertificateIssuedNotification($certificate));
        }

        // Award bonus points for certificate
        $certificatePoints = 0;
        if ($user->gamification) {
            $certificatePoints = app(GamificationService::class)->awardConfiguredPoints($user, 'certificate_earned');
        }

        return redirect()->route('learner.certificates.show', $certificate)
            ->with('success', "Congratulations! Your certificate has been generated! You earned {$certificatePoints} bonus points! 🎉");
    }

    /**
     * Show certificate
     */
    public function show(Certificate $certificate)
    {
        $user = Auth::user();

        // Security check
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $certificate->loadMissing('module');

        $eligibilityError = $this->getEligibilityError($user->id, $certificate->module);
        if ($eligibilityError) {
            return redirect()
                ->route('learner.modules.show', $certificate->module)
                ->with('error', $eligibilityError);
        }

        $templateImageUrl = app(CertificatePdfService::class)->getTemplatePublicUrl();

        return view('learner.certificates.show', compact('certificate', 'templateImageUrl'));
    }

    /**
     * Download certificate PDF
     */
    public function download(Certificate $certificate)
    {
        $user = Auth::user();

        // Security check
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $pdfPath = app(CertificatePdfService::class)->ensureStoredPdf($certificate);
        $downloadName = 'certificate-' . $certificate->certificate_number . '.pdf';

        return response()->download(Storage::disk('public')->path($pdfPath), $downloadName);
    }

    private function getEligibilityError(int $userId, Module $module): ?string
    {
        if (!Auth::user()->moduleEnrollments()
            ->where('module_id', $module->id)
            ->where('status', EnrollmentStatus::Approved)
            ->exists()) {
            return 'You must be enrolled in this module.';
        }

        $lessons = $module->lessons()
            ->where('is_published', true)
            ->with([
                'topics',
                'quiz' => fn ($query) => $query->where('is_active', true),
            ])
            ->get();

        if ($lessons->isEmpty()) {
            return 'No published lessons are available yet for this module.';
        }

        $completedLessonIds = UserProgress::where('user_id', $userId)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->unique();

        if ($completedLessonIds->count() < $lessons->count()) {
            return 'You must complete all lessons before getting a certificate.';
        }

        $topicIds = $lessons->flatMap(fn ($lesson) => $lesson->topics->pluck('id'))->unique();
        if ($topicIds->isNotEmpty()) {
            $completedTopicIds = LessonTopicProgress::where('user_id', $userId)
                ->whereIn('lesson_topic_id', $topicIds)
                ->where('completed', true)
                ->pluck('lesson_topic_id')
                ->unique();

            if ($completedTopicIds->count() < $topicIds->count()) {
                return 'You must complete all lesson topics before getting a certificate.';
            }
        }

        $lessonQuizIds = $lessons
            ->pluck('quiz')
            ->filter()
            ->pluck('id')
            ->unique();

        if ($lessonQuizIds->isNotEmpty()) {
            $lessonQuizById = $lessons
                ->pluck('quiz')
                ->filter()
                ->keyBy('id');

            $allLessonQuizzesCompleted = $lessonQuizIds->every(function ($quizId) use ($userId, $lessonQuizById) {
                $attemptCount = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->count();

                if ($attemptCount === 0) {
                    return false;
                }

                $hasPassed = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->where('passed', true)
                    ->exists();

                if ($hasPassed) {
                    return true;
                }

                $attemptLimit = $lessonQuizById->get($quizId)?->attempt_limit;

                return $attemptLimit !== null && $attemptCount >= (int) $attemptLimit;
            });

            if (!$allLessonQuizzesCompleted) {
                return 'You must complete all lesson quizzes before getting a certificate.';
            }
        }

        if ($module->final_quiz_id) {
            $finalAttemptCount = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $module->final_quiz_id)
                ->count();

            $hasPassedFinalQuiz = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $module->final_quiz_id)
                ->where('passed', true)
                ->exists();

            $finalQuizAttemptLimit = $module->quizzes()
                ->where('id', $module->final_quiz_id)
                ->value('attempt_limit');

            $isFinalQuizCompleted = $hasPassedFinalQuiz
                || ($finalQuizAttemptLimit !== null && $finalAttemptCount >= (int) $finalQuizAttemptLimit);

            if (!$isFinalQuizCompleted) {
                return 'You must complete the final quiz before getting a certificate.';
            }
        }

        return null;
    }
}
