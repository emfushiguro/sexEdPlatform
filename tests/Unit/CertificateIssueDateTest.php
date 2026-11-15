<?php

namespace Tests\Unit;

use App\Models\Certificate;
use Illuminate\Foundation\Testing\TestCase;

class CertificateIssueDateTest extends TestCase
{
    public function test_issue_date_uses_display_timezone_and_remains_the_issuance_date(): void
    {
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Manila']);
        $certificate = new Certificate(['issued_at' => '2026-11-11 16:30:00']);

        $this->assertSame('November 12, 2026', $certificate->issued_date->format('F d, Y'));
        $this->assertSame('2026-11-11 16:30:00', $certificate->issued_at->format('Y-m-d H:i:s'));
    }
}
