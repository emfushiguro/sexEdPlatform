<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_includes_risk_leakage_and_growth_metric_blocks(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Immediate Risk', false)
            ->assertSee('Revenue Leakage', false)
            ->assertSee('Growth and Platform Health', false)
            ->assertSee('View Subscribers', false)
            ->assertSee('View Payments', false);
    }
}
