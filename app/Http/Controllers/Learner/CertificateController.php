<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Certificate;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Premium check
        if (!$user->isPremium()) {
            return back()->with('error', 'Certificates are only available for Premium members. Upgrade now!');
        }

        // Check if already has certificate
        if (Certificate::where('user_id', $user->id)->where('module_id', $module->id)->exists()) {
            return back()->with('info', 'You already have a certificate for this module.');
        }

        // Check enrollment
        if (!$user->moduleEnrollments()->where('module_id', $module->id)->exists()) {
            return back()->with('error', 'You must be enrolled in this module.');
        }

        // Check all lessons completed
        $totalLessons = $module->lessons()->where('is_published', true)->count();
        $completedLessons = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->count();

        if ($completedLessons < $totalLessons) {
            return back()->with('error', 'You must complete all lessons before getting a certificate.');
        }

        // Check final quiz requirement
        if ($module->final_quiz_id) {
            $bestAttempt = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $module->final_quiz_id)
                ->orderBy('score', 'desc')
                ->first();

            if (!$bestAttempt || $bestAttempt->score < $module->certificate_pass_score) {
                $required = $module->certificate_pass_score;
                $current = $bestAttempt ? $bestAttempt->score : 0;
                return back()->with('error', "You need to pass the final quiz with {$required}% or higher. Your best score: {$current}%");
            }
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
            'issued_at' => now(),
        ]);

        // Award bonus points for certificate
        if ($user->gamification) {
            app(GamificationService::class)->awardPoints($user, 'certificate_earned', 50);
        }

        return redirect()->route('learner.certificates.show', $certificate)
            ->with('success', 'Congratulations! Your certificate has been generated! You earned 50 bonus points! 🎉');
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

        return view('learner.certificates.show', compact('certificate'));
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

        // TODO: Generate PDF certificate
        // For now, just return the view
        return view('learner.certificates.pdf', compact('certificate'));
    }
}
