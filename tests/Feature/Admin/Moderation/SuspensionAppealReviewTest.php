<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionAppealReviewTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_review_actions_support_approve_reject_and_clarification_requested(): void
    {
        $service = app(SuspensionAppealService::class);

        $admin = User::factory()->create();

        $approveAppeal = $this->makeAppeal();
        $approved = $service->reviewAppeal($approveAppeal, $admin, 'approve', 'Approved after verification.');
        $this->assertSame('approved', $approved->status);

        $rejectAppeal = $this->makeAppeal();
        $rejected = $service->reviewAppeal($rejectAppeal, $admin, 'reject', 'Insufficient evidence.');
        $this->assertSame('rejected', $rejected->status);

        $clarificationAppeal = $this->makeAppeal();
        $clarification = $service->reviewAppeal($clarificationAppeal, $admin, 'clarification_requested', 'Provide additional context.');
        $this->assertSame('clarification_requested', $clarification->status);
        $this->assertNotNull($clarification->clarification_requested_at);
    }

    private function makeAppeal()
    {
        $service = app(SuspensionAppealService::class);
        $user = User::factory()->create();

        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now(),
            'ends_at' => now()->addDays(2),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        $suspension = UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(2),
            'appeal_status' => 'none',
        ]);

        return $service->submitAppeal($suspension, $user, 'Please review my suspension.');
    }
}
