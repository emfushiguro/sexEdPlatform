<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinancialReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_financial_report_page_renders_metrics_and_export_actions(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.financial-reports.index', ['report_type' => 'monthly']))
            ->assertOk()
            ->assertSee('Financial Reporting Dashboard', false)
            ->assertSee('Total Revenue', false)
            ->assertSee('Subscription Revenue', false)
            ->assertSee('Module Revenue', false)
            ->assertSee('Platform Earnings', false)
            ->assertSee('Export PDF', false)
            ->assertSee('Export CSV', false)
            ->assertSee('Export XLSX', false);
    }
}
