<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_context_profile_route_is_accessible(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.profile.show'))
            ->assertOk()
            ->assertSee('My Admin Profile', false)
            ->assertSee('Edit Profile', false)
            ->assertSee(route('admin.profile.edit'), false);
    }
}
