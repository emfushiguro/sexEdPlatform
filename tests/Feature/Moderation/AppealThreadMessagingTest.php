<?php

namespace Tests\Feature\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AppealThreadMessagingTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_messages_are_attached_to_appeals_only(): void
    {
        $service = app(SuspensionAppealService::class);

        $appeal = $this->makeAppeal();
        $message = $service->postThreadMessage($appeal, $appeal->user, 'Please revisit my appeal details.');

        $this->assertDatabaseHas('appeal_thread_messages', [
            'id' => $message->id,
            'suspension_appeal_id' => $appeal->id,
        ]);
    }

    public function test_sender_roles_are_validated(): void
    {
        $service = app(SuspensionAppealService::class);

        $appeal = $this->makeAppeal();
        $outsider = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $service->postThreadMessage($appeal, $outsider, 'I should not be able to post here.');
    }

    public function test_parent_visibility_for_learner_linked_accounts_is_enforced(): void
    {
        $service = app(SuspensionAppealService::class);

        $appeal = $this->makeAppeal();
        $linkedParent = User::factory()->create();

        ParentChildAccount::query()->create([
            'parent_user_id' => $linkedParent->id,
            'child_user_id' => $appeal->user_id,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $parentMessage = $service->postThreadMessage($appeal, $linkedParent, 'Parent follow-up for clarification.');
        $this->assertSame('parent', $parentMessage->sender_role);

        $unlinkedParent = User::factory()->create();
        $this->expectException(\InvalidArgumentException::class);
        $service->postThreadMessage($appeal, $unlinkedParent, 'Unlinked parent should be denied.');
    }

    private function makeAppeal()
    {
        $service = app(SuspensionAppealService::class);
        $learner = User::factory()->create();

        $action = EnforcementAction::query()->create([
            'user_id' => $learner->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now(),
            'ends_at' => now()->addDays(2),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        $suspension = UserSuspension::query()->create([
            'user_id' => $learner->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(2),
            'appeal_status' => 'none',
        ]);

        return $service->submitAppeal($suspension, $learner, 'Please review this case.');
    }
}
