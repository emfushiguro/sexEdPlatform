<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinancialReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_financial_report_as_csv(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.financial-reports.export', ['format' => 'csv', 'report_type' => 'monthly']));

        $response->assertOk();
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
    }
}
