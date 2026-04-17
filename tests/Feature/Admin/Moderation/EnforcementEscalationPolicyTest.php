<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\User;
use App\Services\Moderation\EnforcementActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class EnforcementEscalationPolicyTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_minor_or_moderate_severity_cannot_skip_escalation_ladder(): void
    {
        $service = app(EnforcementActionService::class);
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $service->issueAction(
            user: $user,
            actionType: EnforcementActionType::ExtendedSuspension,
            severity: ViolationSeverity::Moderate,
            triggerType: 'manual',
            skipLadder: true,
            skipRationale: 'Immediate escalation requested',
        );
    }

    public function test_major_or_critical_severity_can_skip_with_rationale(): void
    {
        $service = app(EnforcementActionService::class);
        $user = User::factory()->create();

        $action = $service->issueAction(
            user: $user,
            actionType: EnforcementActionType::ExtendedSuspension,
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            skipLadder: true,
            skipRationale: 'Repeated high-risk behavior with evidence',
        );

        $this->assertDatabaseHas('enforcement_actions', [
            'id' => $action->id,
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::ExtendedSuspension->value,
            'severity_level' => ViolationSeverity::Major->value,
            'skip_ladder' => true,
        ]);
    }

    public function test_permanent_suspension_cannot_be_auto_issued(): void
    {
        $service = app(EnforcementActionService::class);
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $service->issueAction(
            user: $user,
            actionType: EnforcementActionType::PermanentSuspension,
            severity: ViolationSeverity::Critical,
            triggerType: 'automatic',
            skipLadder: true,
            skipRationale: 'Automation attempted permanent action',
        );
    }
}
