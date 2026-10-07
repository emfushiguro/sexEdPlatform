<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\User;
use Tests\TestCase;

class AdminConnectorMarketplaceListingTest extends TestCase
{
    public function test_platform_admin_can_publish_and_suspend_a_platform_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => 'PHP',
                'seat_capacity' => 10,
                'action' => 'publish',
            ])
            ->assertRedirect();

        $listing = ModuleMarketplaceListing::query()->where('module_id', $module->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.modules.marketplace.suspend', $listing), [
                'reason' => 'Administrative review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'id' => $listing->id,
            'status' => ModuleMarketplaceListing::STATUS_SUSPENDED,
            'suspension_reason' => 'Administrative review',
        ]);
    }
}
