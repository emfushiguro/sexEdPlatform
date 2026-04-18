<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceReportingRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_financial_reports_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.financial-reports.index'))
            ->assertOk()
            ->assertSee('Financial Reporting Dashboard');
    }

    public function test_instructor_cannot_access_admin_financial_reports_page(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('admin.financial-reports.index'))
            ->assertForbidden();
    }
}
