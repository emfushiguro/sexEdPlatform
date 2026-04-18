<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Admin\AdminCreatorProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCreatorProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_get_or_create_creates_default_profile_for_admin(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin One',
        ]);
        $admin->assignRole('admin');

        /** @var AdminCreatorProfileService $service */
        $service = app(AdminCreatorProfileService::class);

        $profile = $service->getOrCreateForUser($admin);

        $this->assertSame($admin->id, $profile->user_id);
        $this->assertSame('Admin One', $profile->public_display_name);
        $this->assertSame('Conscious Connections Team', $profile->affiliation);
        $this->assertFalse((bool) $profile->show_individual_attribution);
    }

    public function test_service_updates_profile_payload_fields(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Two',
        ]);
        $admin->assignRole('admin');

        /** @var AdminCreatorProfileService $service */
        $service = app(AdminCreatorProfileService::class);

        $profile = $service->updateFromValidatedPayload($admin, [
            'public_display_name' => 'Creator Two',
            'bio' => 'Platform engineering lead.',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $this->assertSame('Creator Two', $profile->public_display_name);
        $this->assertSame('Platform engineering lead.', $profile->bio);
        $this->assertTrue((bool) $profile->show_individual_attribution);
    }
}
