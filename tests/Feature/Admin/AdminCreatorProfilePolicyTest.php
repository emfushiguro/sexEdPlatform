<?php

namespace Tests\Feature\Admin;

use App\Models\AdminCreatorProfile;
use App\Models\User;
use App\Policies\AdminCreatorProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCreatorProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creator_profile_policy_allows_owner_update_and_blocks_non_owner(): void
    {
        Role::findOrCreate('admin', 'web');

        $owner = User::factory()->create(['role' => 'admin']);
        $owner->assignRole('admin');

        $other = User::factory()->create(['role' => 'admin']);
        $other->assignRole('admin');

        $profile = AdminCreatorProfile::query()->create([
            'user_id' => $owner->id,
            'public_display_name' => 'Owner Admin',
            'affiliation' => 'Conscious Connections Team',
        ]);

        $policy = new AdminCreatorProfilePolicy();

        $this->assertTrue($policy->update($owner, $profile));
        $this->assertFalse($policy->update($other, $profile));
    }
}
