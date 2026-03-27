<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Module;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class CertificateNumberFormatTest extends TestCase
{
    public function test_certificate_number_uses_cc_prefix_and_issued_year_on_create(): void
    {
        $user = User::factory()->create();
        $module = Module::factory()->create();
        $issuedAt = Carbon::create(2024, 5, 12, 10, 0, 0);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'issued_at' => $issuedAt,
        ]);

        $this->assertMatchesRegularExpression('/^CC-2024-[A-Z0-9]{8}$/', $certificate->certificate_number);
    }

    public function test_generate_certificate_number_uses_cc_current_year_format(): void
    {
        $year = now()->format('Y');
        $generated = Certificate::generateCertificateNumber();

        $this->assertMatchesRegularExpression('/^CC-' . $year . '-[A-Z0-9]{8}$/', $generated);
    }
}
