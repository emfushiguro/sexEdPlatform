<?php

namespace Tests\Feature\Admin;

use App\Models\CommissionPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommissionPolicyAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_create_and_update_write_audit_rows(): void
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
                'effective_from' => now()->subMinute()->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.monetization.commission-settings.index'));

        $policy = CommissionPolicy::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('commission_policy_audits', [
            'actor_admin_id' => $admin->id,
            'action_type' => 'created',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.monetization.commission-settings.update', $policy), [
                'scope_type' => 'global',
                'commission_percent' => 12.50,
                'tax_basis' => 'gross',
                'refund_policy' => 'disabled',
                'is_active' => true,
                'effective_from' => now()->toDateTimeString(),
                'effective_to' => now()->addDays(10)->toDateTimeString(),
            ])
            ->assertRedirect(route('admin.monetization.commission-settings.index'));

        $this->assertDatabaseHas('commission_policy_audits', [
            'actor_admin_id' => $admin->id,
            'action_type' => 'updated',
        ]);
    }

    public function test_non_admin_cannot_write_commission_policy(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->post(route('admin.monetization.commission-settings.store'), [
                'scope_type' => 'global',
                'commission_percent' => 10.00,
                'tax_basis' => 'gross',
                'refund_policy' => 'disabled',
                'is_active' => true,
                'effective_from' => now()->toDateTimeString(),
            ])
            ->assertForbidden();
    }
}
