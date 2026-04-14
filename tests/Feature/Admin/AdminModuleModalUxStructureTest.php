<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminModuleModalUxStructureTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_module_modal_uses_constrained_shell_with_scrollable_body_and_persistent_footer(): void
    {
        $admin = $this->createUser('admin');

        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('data-testid="module-modal-shell"', false);
        $response->assertSee('data-testid="module-modal-header"', false);
        $response->assertSee('data-testid="module-modal-body"', false);
        $response->assertSee('data-testid="module-modal-footer"', false);
        $response->assertSee('max-h-[92vh]', false);
        $response->assertSee('overflow-hidden', false);
        $response->assertSee('overflow-y-auto', false);
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}