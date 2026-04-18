<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Services\Moderation\SuspensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class UserSuspensionLifecycleTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_active_expired_and_revoked_state_transitions(): void
    {
        $service = app(SuspensionService::class);

        $user = User::factory()->create();
        $admin = User::factory()->create();

        $activeAction = $this->makeEnforcementAction($user, EnforcementActionType::TemporarySuspension, now()->addHours(4));
        $activeSuspension = $service->createFromEnforcementAction($activeAction, $admin);

        $this->assertSame('active', $activeSuspension->status);

        $activeSuspension->forceFill([
            'ends_at' => now()->subMinute(),
        ])->save();

        $expiredSuspension = $service->refreshState($activeSuspension->fresh());
        $this->assertSame('expired', $expiredSuspension->status);

        $revokableAction = $this->makeEnforcementAction($user, EnforcementActionType::ExtendedSuspension, now()->addDays(3));
        $revokableSuspension = $service->createFromEnforcementAction($revokableAction, $admin);

        $revokedSuspension = $service->revoke($revokableSuspension, $admin, 'Manual intervention approved.');

        $this->assertSame('revoked', $revokedSuspension->status);
        $this->assertNotNull($revokedSuspension->revoked_at);
        $this->assertSame($admin->id, $revokedSuspension->revoked_by_admin_id);
    }

    public function test_permanent_suspension_stores_null_ends_at(): void
    {
        $service = app(SuspensionService::class);

        $user = User::factory()->create();
        $admin = User::factory()->create();

        $permanentAction = $this->makeEnforcementAction($user, EnforcementActionType::PermanentSuspension, null);
        $suspension = $service->createFromEnforcementAction($permanentAction, $admin);

        $this->assertNull($suspension->ends_at);
        $this->assertSame('active', $suspension->status);
    }

    public function test_appeal_pending_state_handling(): void
    {
        $service = app(SuspensionService::class);

        $user = User::factory()->create();
        $admin = User::factory()->create();

        $action = $this->makeEnforcementAction($user, EnforcementActionType::TemporarySuspension, now()->addDays(2));
        $suspension = $service->createFromEnforcementAction($action, $admin);

        $appealPending = $service->markAppealPending($suspension);

        $this->assertSame('appeal_pending', $appealPending->appeal_status);
        $this->assertNotNull($appealPending->appeal_submitted_at);
    }

    private function makeEnforcementAction(
        User $user,
        EnforcementActionType $actionType,
        $endsAt,
    ): EnforcementAction {
        return EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'status' => 'executed',
            'skip_ladder' => false,
        ]);
    }
}
