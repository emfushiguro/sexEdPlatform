<?php

namespace Tests\Feature\Admin;

use App\Models\AdminCreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCreatorProfileRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_admin_creator_profile(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $profile = AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Creator Admin',
            'affiliation' => 'Conscious Connections Team',
        ]);

        $this->assertNotNull($admin->adminCreatorProfile);
        $this->assertTrue($admin->adminCreatorProfile->is($profile));
    }

    public function test_admin_creator_profile_belongs_to_user(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $profile = AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Identity Owner',
            'affiliation' => 'Conscious Connections Team',
        ]);

        $this->assertNotNull($profile->user);
        $this->assertTrue($profile->user->is($admin));
    }
}
