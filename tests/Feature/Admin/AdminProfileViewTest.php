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

    public function test_admin_profile_show_and_edit_views_render_expected_identity_fields(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

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
            ->assertSee('Edit Profile', false);

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Edit Admin Profile', false)
            ->assertSee('Public Display Name', false)
            ->assertSee('Affiliation', false)
            ->assertSee('Show Individual Attribution', false)
            ->assertDontSee('Permissions', false);
    }
}
