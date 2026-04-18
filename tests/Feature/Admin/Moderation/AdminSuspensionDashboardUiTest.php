<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AdminSuspensionDashboardUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_stat_cards_and_table_rows(): void
    {
        $admin = $this->createUserWithRole('admin');
        $suspendedLearner = $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'appeal_pending',
            name: 'Learner One',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('Suspension Dashboard', false)
            ->assertSee('Active Suspensions', false)
            ->assertSee('Appeals Pending', false)
            ->assertSee('Permanent Suspensions', false)
            ->assertSeeText($suspendedLearner->name)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-table-pagination-footer"', false);
    }

    public function test_filters_apply_role_severity_trigger_and_status(): void
    {
        $admin = $this->createUserWithRole('admin');

        $matching = $this->createSuspensionRecord(
            role: 'instructor',
            severity: ViolationSeverity::Minor,
            triggerType: 'automated',
            suspensionStatus: 'revoked',
            appealStatus: 'none',
            name: 'Instructor Match',
        );

        $nonMatching = $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Critical,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'appeal_pending',
            name: 'Learner Other',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'role' => 'instructor',
                'severity' => 'minor',
                'trigger' => 'automated',
                'status' => 'revoked',
            ]))
            ->assertOk()
            ->assertSeeText($matching->name)
            ->assertDontSeeText($nonMatching->name);
    }

    public function test_search_and_pagination_work_together(): void
    {
        $admin = $this->createUserWithRole('admin');

        for ($i = 1; $i <= 16; $i++) {
            $this->createSuspensionRecord(
                role: 'learner',
                severity: ViolationSeverity::Moderate,
                triggerType: 'manual',
                suspensionStatus: 'active',
                appealStatus: 'none',
                name: 'Paged Learner ' . $i,
            );
        }

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertSeeText('Paged Learner 1');

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index', [
                'search' => 'Paged Learner 12',
            ]))
            ->assertOk()
            ->assertSeeText('Paged Learner 12')
            ->assertDontSeeText('Paged Learner 8');
    }

    public function test_dashboard_uses_payment_management_style_markers(): void
    {
        $admin = $this->createUserWithRole('admin');
        $this->createSuspensionRecord(
            role: 'learner',
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            suspensionStatus: 'active',
            appealStatus: 'none',
            name: 'Style Marker User',
        );

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.index'))
            ->assertOk()
            ->assertSee('rounded-[30px]', false)
            ->assertSee('border-brand-100', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-table-pagination-footer"', false);
    }

    private function createUserWithRole(string $role, ?string $name = null): User
    {
        $user = User::factory()->create([
            'name' => $name ?? ucfirst($role) . ' User',
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createSuspensionRecord(
        string $role,
        ViolationSeverity $severity,
        string $triggerType,
        string $suspensionStatus,
        string $appealStatus,
        string $name,
    ): User {
        $user = $this->createUserWithRole($role, $name);

        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => $severity,
            'trigger_type' => $triggerType,
            'starts_at' => now()->subDay(),
            'ends_at' => $suspensionStatus === 'active' ? now()->addDays(3) : now()->subHours(2),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => $suspensionStatus,
            'starts_at' => now()->subDay(),
            'ends_at' => $suspensionStatus === 'active' ? now()->addDays(3) : now()->subHours(2),
            'appeal_status' => $appealStatus,
            'appeal_submitted_at' => $appealStatus === 'appeal_pending' ? now()->subHours(6) : null,
        ]);

        return $user;
    }
}
