<?php

namespace Tests\Feature\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\AppealThreadMessage;
use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionAppealUiFlowTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_moderation_status_and_appeal_use_platform_branding(): void
    {
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);
        foreach ([route('moderation.suspension-status'), route('moderation.appeals.create', $suspension)] as $url) {
            $this->actingAs($learner)->get($url)->assertOk()->assertSee('media/Logo.png', false)
                ->assertSee('Poppins', false)->assertSee('data-moderation-layout', false)
                ->assertDontSee('figtree', false)->assertDontSee('Ask About This Topic');
        }
    }

    public function test_appeal_ownership_guests_and_escaped_history_are_preserved(): void
    {
        $owner = $this->createUserWithRole('learner');
        $other = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($owner);
        $this->get(route('moderation.appeals.create', $suspension))->assertRedirect(route('login'));
        $this->withoutMiddleware(\App\Http\Middleware\EnsureLearnerIdentityVerified::class);
        $this->actingAs($other)->get(route('moderation.appeals.create', $suspension))->assertForbidden();
        SuspensionAppeal::create(['user_id' => $owner->id, 'user_suspension_id' => $suspension->id, 'status' => 'pending_review',
            'appeal_reason' => '<script>alert("test")</script>', 'submitted_at' => now()]);
        $this->actingAs($owner)->get(route('moderation.appeals.create', $suspension))->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("test")</script>', false);
        $this->post(route('moderation.appeals.store', $suspension), ['appeal_reason' => 'short'])->assertSessionHasErrors('appeal_reason');
    }

    public function test_permanent_suspension_does_not_claim_to_be_temporary(): void
    {
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);
        $suspension->enforcementAction->update(['action_type' => 'permanent_suspension', 'ends_at' => null]);
        $suspension->update(['ends_at' => null]);
        $this->actingAs($learner)->get(route('moderation.suspension-status'))->assertOk()
            ->assertSeeText('Your account is suspended')->assertDontSeeText('Your access is temporarily restricted')->assertSeeText('Permanent');
    }

    public function test_suspended_user_can_log_out_without_redirect_loop(): void
    {
        $learner = $this->createUserWithRole('learner');
        $this->createAppealableSuspension($learner);
        $this->actingAs($learner)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_can_submit_appeal_from_suspension_status_flow(): void
    {
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);

        $this->actingAs($learner)
            ->get(route('moderation.suspension-status'))
            ->assertOk()
            ->assertSee('Submit Appeal', false);

        $this->actingAs($learner)
            ->get(route('moderation.appeals.create', $suspension))
            ->assertOk()
            ->assertSee('Submit Suspension Appeal', false)
            ->assertSee('name="appeal_reason"', false);

        $this->actingAs($learner)
            ->post(route('moderation.appeals.store', $suspension), [
                'appeal_reason' => 'I have provided updated clarification and supporting context for reconsideration.',
                'evidence_payload' => ['documents' => ['updated-context.pdf']],
            ])
            ->assertRedirect(route('moderation.suspension-status'));

        $this->assertDatabaseHas('suspension_appeals', [
            'user_suspension_id' => $suspension->id,
            'user_id' => $learner->id,
            'status' => 'pending_review',
        ]);

        $this->assertDatabaseHas('user_suspensions', [
            'id' => $suspension->id,
            'appeal_status' => 'appeal_pending',
        ]);
        $this->get(route('moderation.appeals.create', $suspension))->assertOk()
            ->assertDontSee('name="appeal_reason"', false)
            ->assertDontSee('name="evidence_payload[notes]"', false)
            ->assertSeeText('Appeal History');
    }

    public function test_status_and_messages_are_visible_in_user_appeal_view(): void
    {
        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);

        $appeal = SuspensionAppeal::query()->create([
            'user_suspension_id' => $suspension->id,
            'user_id' => $learner->id,
            'status' => 'clarification_requested',
            'appeal_reason' => 'Please review additional details.',
            'submitted_at' => now()->subHours(4),
            'clarification_requested_at' => now()->subHours(2),
        ]);

        AppealThreadMessage::query()->create([
            'suspension_appeal_id' => $appeal->id,
            'sender_user_id' => $admin->id,
            'sender_role' => 'admin',
            'message_body' => 'Please clarify timeline and submit supporting evidence.',
        ]);

        $this->actingAs($learner)
            ->get(route('moderation.appeals.create', $suspension))
            ->assertOk()
            ->assertSee('clarification requested', false)
            ->assertSee('Please clarify timeline and submit supporting evidence.', false);
        $this->get(route('moderation.appeals.create', $suspension))->assertOk()
            ->assertDontSee('name="appeal_reason"', false)->assertSeeText('Provide clarification');
        $this->get(route('moderation.appeals.create', [$suspension, 'clarify' => 1]))->assertOk()
            ->assertSee('name="appeal_reason"', false)->assertSee('name="evidence_payload[notes]"', false);
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

    private function createAppealableSuspension(User $user): UserSuspension
    {
        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(2),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        return UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(2),
            'appeal_status' => 'none',
        ]);
    }
}
