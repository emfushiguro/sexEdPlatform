<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class CertificateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $certificates = $user->certificates()->with('module')->latest()->get();
        
        return view('learner.certificates.index', compact('certificates'));
    }
    
    public function check(Module $module)
    {
        $user = Auth::user();
        
        // Check if user completed all lessons in the module
        $totalLessons = $module->lessons()->where('is_published', true)->count();
        $completedLessons = $user->userProgress()
            ->where('module_id', $module->id)
            ->where('status', 'completed')
            ->count();
            
        if ($completedLessons < $totalLessons) {
            return redirect()->back()->with('error', 'You must complete all lessons to generate a certificate.');
        }
        
        // Check if certificate already exists
        $existingCert = $user->certificates()->where('module_id', $module->id)->first();
        if ($existingCert) {
            return redirect()->route('learner.certificates.show', $existingCert)
                ->with('info', 'Certificate already generated.');
        }
        
        // Generate certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'issued_at' => now()
        ]);
        
        return redirect()->route('learner.certificates.show', $certificate)
            ->with('success', 'Certificate generated successfully!');
    }
    
    public function show(Certificate $certificate)
    {
        // Ensure user owns this certificate
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }
        
        $certificate->load(['user', 'module']);
        
        return view('learner.certificates.show', compact('certificate'));
    }
    
    public function download(Certificate $certificate)
    {
        // Ensure user owns this certificate
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }
        
        $certificate->load(['user', 'module']);
        
        // Generate PDF (you'll need to implement PDF generation)
        $pdf = $this->generateCertificatePDF($certificate);
        
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="certificate-' . $certificate->certificate_number . '.pdf"');
    }
    
    private function generateCertificatePDF($certificate)
    {
        // Implement PDF generation logic here
        // You can use libraries like dompdf or tcpdf
        return '';
    }
}