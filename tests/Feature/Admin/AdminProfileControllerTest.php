<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_edit_profile_route_and_update_allowed_fields(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        /** @var User $admin */
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

    public function test_admin_can_update_credentials_tab_without_public_profile_fields(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Manager',
            'status' => 'active',
            'password' => Hash::make('CurrentPassword1!'),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'profile_tab' => 'credentials',
                'email' => 'admin.manager.secure@gmail.com',
                'current_password' => 'CurrentPassword1!',
                'new_password' => 'SecurePass2@',
                'new_password_confirmation' => 'SecurePass2@',
            ])
            ->assertRedirect(route('admin.profile.show'));

        $admin->refresh();

        $this->assertSame('admin.manager.secure@gmail.com', $admin->email);
        $this->assertTrue(Hash::check('SecurePass2@', $admin->password));
    }

    public function test_credentials_tab_rejects_weak_passwords(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Manager',
            'status' => 'active',
            'password' => Hash::make('CurrentPassword1!'),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.profile.edit', ['tab' => 'credentials']))
            ->put(route('admin.profile.update'), [
                'profile_tab' => 'credentials',
                'email' => 'admin.manager.secure@gmail.com',
                'current_password' => 'CurrentPassword1!',
                'new_password' => 'weakpass',
                'new_password_confirmation' => 'weakpass',
            ])
            ->assertRedirect(route('admin.profile.edit', ['tab' => 'credentials']))
            ->assertSessionHasErrors('new_password');
    }

    public function test_credentials_tab_rejects_non_gmail_admin_login_email(): void
    {
        $this->withoutVite();

        Role::findOrCreate('admin', 'web');

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Manager',
            'status' => 'active',
            'password' => Hash::make('CurrentPassword1!'),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.profile.edit', ['tab' => 'credentials']))
            ->put(route('admin.profile.update'), [
                'profile_tab' => 'credentials',
                'email' => 'admin.manager@yahoo.com',
            ])
            ->assertRedirect(route('admin.profile.edit', ['tab' => 'credentials']))
            ->assertSessionHasErrors('email');
    }
}
