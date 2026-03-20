<?php

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    private const TEMPLATE_PATH = 'media/certificates/module-certificate-template.png';

    public function ensureStoredPdf(Certificate $certificate): string
    {
        $disk = Storage::disk('public');

        if ($certificate->pdf_path && $disk->exists($certificate->pdf_path)) {
            return $certificate->pdf_path;
        }

        $certificate->loadMissing(['user', 'module']);

        $relativePath = 'certificates/generated/' . $certificate->certificate_number . '.pdf';

        $pdf = $this->buildPdf($certificate);

        $disk->put($relativePath, $pdf->output());

        if ($certificate->pdf_path !== $relativePath) {
            $certificate->forceFill([
                'pdf_path' => $relativePath,
            ])->save();
        }

        return $relativePath;
    }

    private function buildPdf(Certificate $certificate)
    {
        $templateAbsolutePath = public_path(self::TEMPLATE_PATH);

        if (is_file($templateAbsolutePath)) {
            $templateImageBase64 = base64_encode((string) file_get_contents($templateAbsolutePath));

            return Pdf::loadView('learner.certificates.pdf-overlay', [
                'certificate' => $certificate,
                'templateImageBase64' => $templateImageBase64,
            ])->setPaper('a4', 'landscape');
        }

        return Pdf::loadView('learner.certificates.pdf', [
            'certificate' => $certificate,
        ])->setPaper('a4', 'landscape');
    }
}
