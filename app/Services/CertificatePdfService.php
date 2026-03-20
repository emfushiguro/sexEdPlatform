<?php

namespace App\Services;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Throwable;

class CertificatePdfService
{
    private const STORAGE_TEMPLATE_PATH = 'certificate-template/module-certificate-template.png';

    private const CHROME_PATH_CANDIDATES = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    ];

    public function ensureStoredPdf(Certificate $certificate): string
    {
        $disk = Storage::disk('public');

        $certificate->loadMissing(['user', 'module']);

        $relativePath = 'certificates/generated/' . $certificate->certificate_number . '.pdf';

        $pdfBinary = $this->buildPdfBinary($certificate);

        $disk->put($relativePath, $pdfBinary);

        if ($certificate->pdf_path !== $relativePath) {
            $certificate->forceFill([
                'pdf_path' => $relativePath,
            ])->save();
        }

        return $relativePath;
    }

    private function buildPdfBinary(Certificate $certificate): string
    {
        $templateData = $this->resolveTemplateData();

        if ($templateData && extension_loaded('gd') && function_exists('imagecreatefrompng')) {
            $templateImageBase64 = base64_encode($templateData['binary']);

            return Pdf::loadView('learner.certificates.pdf-overlay', [
                'certificate' => $certificate,
                'templateImageBase64' => $templateImageBase64,
            ])->setPaper('a4', 'landscape')->output();
        }

        if ($templateData && class_exists(Browsershot::class)) {
            try {
                return $this->buildPdfWithBrowsershot($certificate, base64_encode($templateData['binary']));
            } catch (Throwable $exception) {
                Log::warning('Certificate browser-rendered PDF fallback failed.', [
                    'certificate_id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return Pdf::loadView('learner.certificates.pdf', [
            'certificate' => $certificate,
        ])->setPaper('a4', 'landscape')->output();
    }

    private function buildPdfWithBrowsershot(Certificate $certificate, string $templateImageBase64): string
    {
        $html = view('learner.certificates.pdf-browser', [
            'certificate' => $certificate,
            'templateImageBase64' => $templateImageBase64,
        ])->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->margins(0, 0, 0, 0)
            ->showBackground();

        $nodeBinary = config('services.browsershot.node_binary');
        if (is_string($nodeBinary) && $nodeBinary !== '') {
            $browsershot->setNodeBinary($nodeBinary);
        }

        $npmBinary = config('services.browsershot.npm_binary');
        if (is_string($npmBinary) && $npmBinary !== '') {
            $browsershot->setNpmBinary($npmBinary);
        }

        $nodeModulePath = base_path('node_modules');
        if (is_dir($nodeModulePath)) {
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        $chromePath = config('services.browsershot.chrome_path');
        if (!is_string($chromePath) || $chromePath === '') {
            $chromePath = $this->detectChromePath();
        }

        if (is_string($chromePath) && $chromePath !== '' && is_file($chromePath)) {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot->pdf();
    }

    private function detectChromePath(): ?string
    {
        foreach (self::CHROME_PATH_CANDIDATES as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function getTemplatePublicUrl(): ?string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists(self::STORAGE_TEMPLATE_PATH)) {
            return null;
        }

        return asset('storage/' . self::STORAGE_TEMPLATE_PATH);
    }

    /**
     * @return array{binary: string}|null
     */
    private function resolveTemplateData(): ?array
    {
        $disk = Storage::disk('public');

        if (!$disk->exists(self::STORAGE_TEMPLATE_PATH)) {
            return null;
        }

        return [
            'binary' => (string) $disk->get(self::STORAGE_TEMPLATE_PATH),
        ];
    }
}
