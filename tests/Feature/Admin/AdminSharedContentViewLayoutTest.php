<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedContentViewLayoutTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_modules_index_uses_admin_layout_and_admin_prefixed_links(): void
    {
        $admin = $this->createAdmin();

        Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Layout Module',
            'is_published' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.modules.index'));

        $response->assertOk();
        $response->assertSee('Admin Panel');
        $response->assertSee('/admin/enrollments', false);
        $response->assertSee('Platform Layout Module');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
