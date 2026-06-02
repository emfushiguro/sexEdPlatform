<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Tests\TestCase;

class ConnectorOrganizationReplacementTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_old_admin_organization_pages_are_not_registered_and_connector_pages_are_supported(): void
    {
        $this->seedCaviteAddress();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->assertFalse(\Route::has('admin.organizations.index'));
        $this->assertTrue(\Route::has('admin.connectors.index'));
        $this->actingAs($admin)->get(route('admin.connectors.index'))->assertOk();
    }
}
