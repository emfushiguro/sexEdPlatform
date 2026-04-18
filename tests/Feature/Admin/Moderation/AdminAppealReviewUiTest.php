<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\AppealThreadMessage;
use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AdminAppealReviewUiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_process_appeals(): void
    {
        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $appeal = $this->createAppeal($learner);

        $this->actingAs($admin)
            ->get(route('admin.moderation-appeals.index'))
            ->assertOk()
            ->assertSeeText($learner->name)
            ->assertSee('Pending Review', false);

        $this->actingAs($admin)
            ->post(route('admin.moderation-appeals.review', $appeal), [
                'action' => 'clarification_requested',
                'review_decision_notes' => 'Please clarify the timeline and provide additional context.',
            ])
            ->assertRedirect(route('admin.moderation-appeals.show', $appeal));

        $this->assertDatabaseHas('suspension_appeals', [
            'id' => $appeal->id,
            'status' => 'clarification_requested',
            'reviewed_by_admin_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.moderation-appeals.show', $appeal))
            ->assertOk()
            ->assertSee('clarification requested', false)
            ->assertSee('Please clarify the timeline and provide additional context.', false);
    }

    public function test_admin_can_post_thread_response_and_message_is_visible(): void
    {
        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $appeal = $this->createAppeal($learner);

        $this->actingAs($admin)
            ->post(route('admin.moderation-appeals.thread.store', $appeal), [
                'message_body' => 'Please attach your supporting document and clarify date details.',
            ])
            ->assertRedirect(route('admin.moderation-appeals.show', $appeal));

        $this->assertDatabaseHas('appeal_thread_messages', [
            'suspension_appeal_id' => $appeal->id,
            'sender_user_id' => $admin->id,
            'sender_role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.moderation-appeals.show', $appeal))
            ->assertOk()
            ->assertSee('Please attach your supporting document and clarify date details.', false);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function createAppeal(User $learner): SuspensionAppeal
    {
        $action = EnforcementAction::query()->create([
            'user_id' => $learner->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        $suspension = UserSuspension::query()->create([
            'user_id' => $learner->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),
            'appeal_status' => 'appeal_pending',
            'appeal_submitted_at' => now()->subHours(5),
        ]);

        $appeal = SuspensionAppeal::query()->create([
            'user_suspension_id' => $suspension->id,
            'user_id' => $learner->id,
            'status' => 'pending_review',
            'appeal_reason' => 'I want this decision reviewed with additional context.',
            'submitted_at' => now()->subHours(5),
        ]);

        AppealThreadMessage::query()->create([
            'suspension_appeal_id' => $appeal->id,
            'sender_user_id' => $learner->id,
            'sender_role' => 'learner',
            'message_body' => 'Initial appeal submission details.',
        ]);

        return $appeal;
    }
}
