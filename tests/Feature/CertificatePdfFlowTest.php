<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificatePdfService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CertificatePdfFlowTest extends TestCase
{
    public function test_certificate_uses_snapshot_fields_when_present(): void
    {
        $user = User::factory()->create(['name' => 'Original Learner Name']);
        $module = Module::factory()->create(['title' => 'Original Module Title']);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => 'Snapshot Learner Name',
            'module_title_snapshot' => 'Snapshot Module Title',
            'issued_at' => Carbon::create(2026, 3, 20, 9, 0, 0),
        ]);

        $this->assertSame('Snapshot Learner Name', $certificate->learner_name);
        $this->assertSame('Snapshot Module Title', $certificate->module_title);
    }

    public function test_certificate_pdf_service_generates_and_stores_pdf_path(): void
    {
        $user = User::factory()->create(['name' => 'PDF Learner']);
        $module = Module::factory()->create(['title' => 'PDF Module']);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => 'PDF Learner',
            'module_title_snapshot' => 'PDF Module',
            'issued_at' => Carbon::create(2026, 3, 20, 9, 0, 0),
        ]);

        $pdfPath = app(CertificatePdfService::class)->ensureStoredPdf($certificate);

        $this->assertStringContainsString('certificates/generated/', $pdfPath);
        $this->assertTrue(Storage::disk('public')->exists($pdfPath));

        $certificate->refresh();

        $this->assertSame($pdfPath, $certificate->pdf_path);
    }

    public function test_certificate_templates_wrap_long_recipient_and_module_names_without_ellipsis(): void
    {
        $user = User::factory()->create([
            'name' => 'Alexandria Mariella Santos Del Rosario',
        ]);
        $module = Module::factory()->create([
            'title' => 'Condoms and Safer Sexual Practices for Healthy Relationships',
        ]);
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => $user->name,
            'module_title_snapshot' => $module->title,
            'issued_at' => Carbon::create(2026, 3, 20, 9, 0, 0),
        ]);

        $this->actingAs($user);

        $preview = view('learner.certificates.show', [
            'certificate' => $certificate,
            'templateImageUrl' => 'https://example.test/certificate.png',
            'errors' => new ViewErrorBag,
        ])->render();
        $browserPdf = view('learner.certificates.pdf-browser', [
            'certificate' => $certificate,
            'templateImageBase64' => 'template',
        ])->render();
        $overlayPdf = view('learner.certificates.pdf-overlay', [
            'certificate' => $certificate,
            'templateImageBase64' => 'template',
        ])->render();

        foreach ([$preview, $browserPdf, $overlayPdf] as $template) {
            $this->assertStringContainsString('overflow-wrap: anywhere', $template);
            $this->assertStringNotContainsString('text-overflow: ellipsis', $template);
        }

        $this->assertMatchesRegularExpression(
            '/\.certificate-overlay-name\s*\{[^}]*white-space:\s*nowrap/s',
            $preview,
        );
        $this->assertStringNotContainsString('truncate certificate-overlay-name', $preview);
    }
}
