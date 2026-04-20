<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LandingApkDownloadTest extends TestCase
{
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_download_apk_serves_allowlisted_apk_with_sanitized_filename(): void
    {
        $this->createFile('app/public/apk/test-release.apk', 'test-apk-content');

        config()->set('apk.local_file', 'app/public/apk/test-release.apk');
        config()->set('apk.download_filename', '../unsafe name?.apk');

        $response = $this->get(route('landing.apk'));

        $response->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'unsafe_name_.apk',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_download_apk_rejects_files_outside_allowlisted_directory(): void
    {
        $this->createFile('storage/app/private-leak.apk', 'sensitive');

        config()->set('apk.local_file', 'storage/app/private-leak.apk');

        $this->get(route('landing.apk'))->assertNotFound();
    }

    public function test_download_apk_rejects_non_apk_file_extensions(): void
    {
        $this->createFile('app/public/apk/not-an-apk.txt', 'not-apk');

        config()->set('apk.local_file', 'app/public/apk/not-an-apk.txt');

        $this->get(route('landing.apk'))->assertNotFound();
    }

    public function test_landing_page_uses_local_qr_proxy_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('landing.apk.qr'), false);
        $response->assertDontSee('api.qrserver.com', false);
    }

    private function createFile(string $relativePath, string $contents): string
    {
        $path = base_path($relativePath);
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $contents);

        $this->createdFiles[] = $path;

        return $path;
    }
}
