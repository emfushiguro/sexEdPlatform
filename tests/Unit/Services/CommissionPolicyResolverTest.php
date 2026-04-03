<?php

namespace Tests\Unit\Services;

use App\Models\CommissionPolicy;
use App\Models\User;
use App\Services\Monetization\CommissionPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionPolicyResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_prefers_active_instructor_override_over_global_policy(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => 10.00,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
        ]);

        $override = CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_INSTRUCTOR,
            'scope_id' => $instructor->id,
            'commission_percent' => 8.50,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDay(),
        ]);

        $resolved = app(CommissionPolicyResolver::class)->resolveForInstructor($instructor->id);

        $this->assertSame($override->id, $resolved->id);
    }

    public function test_resolve_respects_effective_windows(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => 12.00,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->addDay(),
        ]);

        $active = CommissionPolicy::query()->create([
            'scope_type' => CommissionPolicy::SCOPE_GLOBAL,
            'scope_id' => null,
            'commission_percent' => 10.00,
            'tax_basis' => 'gross',
            'refund_policy' => 'disabled',
            'is_active' => true,
            'effective_from' => now()->subDays(5),
            'effective_to' => now()->addDay(),
        ]);

        $resolved = app(CommissionPolicyResolver::class)->resolveForInstructor($instructor->id, now());

        $this->assertSame($active->id, $resolved->id);
    }

    public function test_resolve_returns_fallback_when_no_active_policy_available(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);

        config()->set('monetization.default_commission_percent', 11.25);
        config()->set('monetization.default_tax_basis', 'gross');
        config()->set('monetization.default_refund_policy', 'disabled');

        $resolved = app(CommissionPolicyResolver::class)->resolveForInstructor($instructor->id);

        $this->assertSame(CommissionPolicy::SCOPE_GLOBAL, $resolved->scope_type);
        $this->assertSame('11.25', (string) $resolved->commission_percent);
        $this->assertSame('gross', (string) $resolved->tax_basis);
        $this->assertSame('disabled', (string) $resolved->refund_policy);
    }
}
