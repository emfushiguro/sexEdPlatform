<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_edit_profile_route_and_update_allowed_fields(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Manager',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Edit Admin Profile', false);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'public_display_name' => 'Creator Manager',
                'bio' => 'Maintains core platform modules.',
                'affiliation' => 'Conscious Connections Team',
                'show_individual_attribution' => '1',
                'role' => 'learner',
            ])
            ->assertRedirect(route('admin.profile.show'));

        $admin->refresh();

        $this->assertSame('admin', $admin->role);
        $this->assertDatabaseHas('admin_creator_profiles', [
            'user_id' => $admin->id,
            'public_display_name' => 'Creator Manager',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);
    }
}
