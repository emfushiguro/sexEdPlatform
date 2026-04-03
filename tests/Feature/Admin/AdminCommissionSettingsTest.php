<?php

namespace Tests\Feature\Admin;

use App\Models\CommissionPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommissionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_commission_settings_page(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.monetization.commission-settings.index'))
            ->assertOk()
            ->assertSee('Commission Settings', false)
            ->assertSee('Global Default Commission', false)
            ->assertSee('Instructor Override', false);
    }

    public function test_admin_can_create_global_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.monetization.commission-settings.store'), [
                'scope_type' => 'global',
                'commission_percent' => 10.00,
                'tax_basis' => 'gross',
                'refund_policy' => 'disabled',
                'is_active' => true,
                'effective_from' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.monetization.commission-settings.index'));

        $this->assertDatabaseHas('commission_policies', [
            'scope_type' => 'global',
            'scope_id' => null,
            'commission_percent' => 10.00,
            'tax_basis' => 'gross',
        ]);
    }

    public function test_admin_can_create_instructor_override_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($admin)
            ->post(route('admin.monetization.commission-settings.store'), [
                'scope_type' => 'instructor',
                'scope_id' => $instructor->id,
                'commission_percent' => 8.50,
                'tax_basis' => 'gross',
                'refund_policy' => 'disabled',
                'is_active' => true,
                'effective_from' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.monetization.commission-settings.index'));

        $this->assertDatabaseHas('commission_policies', [
            'scope_type' => 'instructor',
            'scope_id' => $instructor->id,
            'commission_percent' => 8.50,
        ]);
    }

    public function test_overlapping_effective_windows_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        CommissionPolicy::query()->create([
            'scope_type' => 'global',
            'scope_id' => null,
            'commission_percent' => 10.00,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
            'effective_to' => now()->addDays(2),
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.monetization.commission-settings.store'), [
                'scope_type' => 'global',
                'commission_percent' => 12.00,
                'tax_basis' => 'gross',
                'refund_policy' => 'disabled',
                'is_active' => true,
                'effective_from' => now()->toDateTimeString(),
                'effective_to' => now()->addDays(4)->toDateTimeString(),
            ])
            ->assertSessionHasErrors(['effective_from']);

        $this->assertSame(1, CommissionPolicy::query()->count());
    }
}
