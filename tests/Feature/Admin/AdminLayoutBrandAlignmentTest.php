<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminLayoutBrandAlignmentTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_sidebar_renders_platform_branding_block(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Administrator Dashboard', false)
            ->assertSee('/media/Logo.png', false)
            ->assertSee('data-testid="admin-sidebar-branding"', false);
    }
}
