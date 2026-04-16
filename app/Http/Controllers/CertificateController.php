<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Module;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateController extends Controller
{
    /**
     * Display user's certificates
     */
    public function index()
    {
        $certificates = auth()->user()->certificates()
            ->with('module')
            ->latest()
            ->paginate(10);

        return view('certificates.index', compact('certificates'));
    }

    /**
     * Generate certificate for completed module
     * Only available for premium users
     */
    public function generate(Module $module)
    {
        $user = auth()->user();

        // Check if user is premium
        if (!$user->isPremium()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'Certificates are only available for premium members.');
        }

        // Check if user has completed the module
        $progress = $user->progress()
            ->where('module_id', $module->id)
            ->where('status', 'completed')
            ->first();

        if (!$progress) {
            return redirect()->back()
                ->with('error', 'You must complete the module first to get a certificate.');
        }

        // Check if certificate already exists
        $existingCertificate = Certificate::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();

        if ($existingCertificate) {
            return redirect()->route('certificates.show', $existingCertificate)
                ->with('info', 'Certificate already exists for this module.');
        }

        DB::beginTransaction();

        try {
            // Create certificate
            $certificate = Certificate::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'issued_date' => now(),
            ]);

            // Award points for getting certificate
            app(GamificationService::class)->awardConfiguredPoints($user, 'certificate_earned');

            DB::commit();

            return redirect()->route('certificates.show', $certificate)
                ->with('success', 'Certificate generated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to generate certificate. Please try again.');
        }
    }

    /**
     * Show certificate details
     */
    public function show(Certificate $certificate)
    {
        // Verify certificate belongs to authenticated user
        if ($certificate->user_id !== auth()->id()) {
            abort(403);
        }

        $certificate->load('module', 'user.profile');

        return view('certificates.show', compact('certificate'));
    }

    /**
     * Download certificate as PDF
     * Only available for premium users
     */
    public function download(Certificate $certificate)
    {
        $user = auth()->user();

        // Verify certificate belongs to authenticated user
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        // Check if user is premium
        if (!$user->isPremium()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'Certificate downloads are only available for premium members.');
        }

        $certificate->load('module', 'user.profile');

        // TODO: Implement PDF generation using Laravel-DomPDF or Laravel-Snappy
        // For now, return a simple view
        return view('certificates.pdf', compact('certificate'));

        /*
         * Example using Laravel-DomPDF (need to install: composer require barryvdh/laravel-dompdf)
         * 
         * $pdf = PDF::loadView('certificates.pdf', compact('certificate'));
         * return $pdf->download('certificate-' . $certificate->certificate_number . '.pdf');
         */
    }

    /**
     * Verify certificate by certificate number
     * Public endpoint - no authentication required
     */
    public function verify(Request $request)
    {
        $request->validate([
            'certificate_number' => 'required|string',
        ]);

        $certificate = Certificate::where('certificate_number', $request->certificate_number)
            ->with('user.profile', 'module')
            ->first();

        if (!$certificate) {
            return view('certificates.verify', [
                'found' => false,
                'message' => 'Invalid certificate number. Please check and try again.',
            ]);
        }

        return view('certificates.verify', [
            'found' => true,
            'certificate' => $certificate,
        ]);
    }

    /**
     * Show certificate verification form
     * Public endpoint - no authentication required
     */
    public function verifyForm()
    {
        return view('certificates.verify-form');
    }
}
