<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminUsersUiAlignmentTest extends TestCase
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

    public function test_users_index_does_not_render_legacy_inline_flash_banners(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->withSession(['success' => 'User updated'])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('bg-success-50 border border-success-200', false)
            ->assertDontSee('bg-error-50 border border-error-200', false)
            ->assertSee('Create User', false);
    }
}
