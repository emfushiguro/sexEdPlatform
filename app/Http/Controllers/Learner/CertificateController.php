<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Certificate;
use App\Models\User;
use App\Notifications\Instructor\LearnerCertificateIssuedNotification;
use App\Notifications\Learner\CertificateIssuedNotification;
use App\Services\CertificatePdfService;
use App\Services\GamificationService;
use App\Services\LearnerModuleCompletionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(private readonly LearnerModuleCompletionService $completionService)
    {
    }

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

        $eligibilityError = $this->getEligibilityError($user, $module);

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

        $eligibilityError = $this->getEligibilityError($user, $certificate->module);
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

    private function getEligibilityError(User $user, Module $module): ?string
    {
        $reason = $this->completionService->completionBlockerReason($user, $module);

        return match ($reason) {
            'Complete all lessons before submitting feedback.' => 'You must complete all lessons before getting a certificate.',
            'Complete all lesson topics before submitting feedback.' => 'You must complete all lesson topics before getting a certificate.',
            'Complete all lesson quizzes before submitting feedback.' => 'You must complete all lesson quizzes before getting a certificate.',
            'Complete the final quiz before submitting feedback.' => 'You must complete the final quiz before getting a certificate.',
            default => $reason,
        };
    }
}
