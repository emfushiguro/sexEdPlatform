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

    public function test_users_index_renders_focused_cards_and_table_contract(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Total Users', false)
            ->assertSee('Active', false)
            ->assertSee('Suspended', false)
            ->assertSee('Archived', false)
            ->assertSee('No.', false)
            ->assertDontSee('Transparency', false)
            ->assertSee('25 / page', false);
    }

    public function test_users_index_uses_column_header_filters_and_delete_modal_pattern(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Search name or email', false)
            ->assertSee('All account types', false)
            ->assertSee('Confirm User Deletion', false)
            ->assertDontSee('All Age Brackets', false)
            ->assertDontSee('Apply', false);
    }

    public function test_user_profile_exposes_modal_based_role_and_status_actions(): void
    {
        $this->withoutVite();
        $admin = $this->createAdminUser();
        $target = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('Change Status', false)
            ->assertSee('Change Role', false)
            ->assertSee('statusModalOpen', false)
            ->assertSee('roleModalOpen', false)
            ->assertDontSee('Update Status', false);
    }
}
