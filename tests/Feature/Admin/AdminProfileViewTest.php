<?php

namespace Tests\Feature\Admin;

use App\Models\AdminCreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_show_and_edit_routes_render_tabbed_profile_editor(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Design Admin',
        ]);
        $admin->assignRole('admin');

        AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Design Creator',
            'bio' => 'Focuses on safe learner experiences.',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.profile.show'))
            ->assertOk()
            ->assertSee('Platform Developer', false)
            ->assertSee('Design Creator', false)
            ->assertSee('Conscious Connections Team', false)
            ->assertSee('Edit Profile', false)
            ->assertSee('Public Profile', false)
            ->assertSee('Account Credentials', false)
            ->assertDontSee('System managed fields', false)
            ->assertDontSee('Permissions', false);

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Edit Admin Profile', false)
            ->assertSee('Display Name', false)
            ->assertSee('Affiliation', false)
            ->assertSee('Admin Login Email', false)
            ->assertSee('Show Individual Attribution', false)
            ->assertSee('Save Public Profile', false)
            ->assertSee('Save Account Credentials', false)
            ->assertDontSee('Permissions', false);
    }
}
