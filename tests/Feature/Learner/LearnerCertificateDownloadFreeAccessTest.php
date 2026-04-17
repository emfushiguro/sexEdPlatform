<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\User;
use App\Services\CertificatePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class LearnerCertificateDownloadFreeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_learner_can_download_certificate_without_premium_subscription_gate(): void
    {
        Storage::fake('public');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
        ]);

        $certificate = Certificate::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => $learner->name,
            'module_title_snapshot' => $module->title,
            'issued_at' => now(),
        ]);

        $pdfPath = 'certificates/generated/test-free-access.pdf';
        Storage::disk('public')->put($pdfPath, 'fake-pdf-content');

        $this->mock(CertificatePdfService::class, function (MockInterface $mock) use ($certificate, $pdfPath): void {
            $mock->shouldReceive('ensureStoredPdf')
                ->once()
                ->withArgs(fn (Certificate $model) => $model->id === $certificate->id)
                ->andReturn($pdfPath);
        });

        $response = $this->actingAs($learner)
            ->get(route('learner.certificates.download', $certificate));

        $response->assertOk();
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
    }
}
