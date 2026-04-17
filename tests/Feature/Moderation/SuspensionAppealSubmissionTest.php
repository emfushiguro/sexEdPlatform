<?php

namespace Tests\Feature\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionAppealSubmissionTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_default_status_is_pending_review(): void
    {
        $service = app(SuspensionAppealService::class);

        $user = User::factory()->create();
        $suspension = $this->makeSuspension($user, EnforcementActionType::TemporarySuspension, now()->addDays(2));

        $appeal = $service->submitAppeal($suspension, $user, 'I want to request review.');

        $this->assertSame('pending_review', $appeal->status);
    }

    public function test_temporary_and_extended_suspensions_are_appealable(): void
    {
        $service = app(SuspensionAppealService::class);
        $user = User::factory()->create();

        $temporarySuspension = $this->makeSuspension($user, EnforcementActionType::TemporarySuspension, now()->addDays(1));
        $extendedSuspension = $this->makeSuspension($user, EnforcementActionType::ExtendedSuspension, now()->addDays(7));

        $temporaryAppeal = $service->submitAppeal($temporarySuspension, $user, 'Temporary appeal request.');
        $extendedAppeal = $service->submitAppeal($extendedSuspension, $user, 'Extended appeal request.');

        $this->assertNotNull($temporaryAppeal->id);
        $this->assertNotNull($extendedAppeal->id);
    }

    public function test_permanent_suspension_appeal_eligibility_is_admin_controlled(): void
    {
        $service = app(SuspensionAppealService::class);
        $user = User::factory()->create();

        $permanentSuspension = $this->makeSuspension($user, EnforcementActionType::PermanentSuspension, null);

        $this->expectException(\InvalidArgumentException::class);
        $service->submitAppeal($permanentSuspension, $user, 'Please review permanent suspension.');
    }

    private function makeSuspension(User $user, EnforcementActionType $type, $endsAt): UserSuspension
    {
        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => $type,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        return UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'appeal_status' => 'none',
        ]);
    }
}
