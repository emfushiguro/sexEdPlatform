<?php

namespace Tests\Feature\Learner;

use App\Models\AdminCreatorProfile;
use App\Models\Module;
use App\Models\User;
use App\Services\Content\AdminOwnershipDisplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOwnershipDisplayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_team_first_fallback_for_admin_owned_module_without_profile(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Owner',
        ]);
        $admin->assignRole('admin');

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);

        $module->loadMissing(['creator', 'creator.adminCreatorProfile']);

        /** @var AdminOwnershipDisplayService $service */
        $service = app(AdminOwnershipDisplayService::class);
        $display = $service->forModule($module);

        $this->assertSame('admin', $display['owner_type']);
        $this->assertSame('Conscious Connections Team', $display['display_owner_name']);
        $this->assertFalse($display['show_individual_attribution']);
        $this->assertNull($display['individual_attribution_text']);
    }

    public function test_service_returns_individual_attribution_text_when_enabled_for_admin_profile(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Owner',
        ]);
        $admin->assignRole('admin');

        AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Creator Persona',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);

        $module->loadMissing(['creator', 'creator.adminCreatorProfile']);

        /** @var AdminOwnershipDisplayService $service */
        $service = app(AdminOwnershipDisplayService::class);
        $display = $service->forModule($module);

        $this->assertSame('Conscious Connections Team', $display['display_owner_name']);
        $this->assertTrue($display['show_individual_attribution']);
        $this->assertSame('by Creator Persona', $display['individual_attribution_text']);
    }
}
