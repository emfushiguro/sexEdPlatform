<?php

namespace Tests\Feature\Notifications;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use App\Notifications\Moderation\AppealDecisionNotification;
use App\Notifications\Moderation\AppealSubmittedNotification;
use App\Notifications\Moderation\EnforcementIssuedNotification;
use App\Notifications\Moderation\SuspensionIssuedNotification;
use App\Services\Moderation\EnforcementActionService;
use App\Services\Moderation\SuspensionAppealService;
use App\Services\Moderation\SuspensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class ModerationLifecycleNotificationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_enforcement_issued_sends_in_app_and_email_notification(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');

        $action = app(EnforcementActionService::class)->issueAction(
            user: $learner,
            actionType: EnforcementActionType::TemporarySuspension,
            severity: ViolationSeverity::Major,
            triggerType: 'manual',
            issuedByAdmin: $admin,
            notes: 'Escalated due to repeated policy violations.',
        );

        Notification::assertSentTo($learner, EnforcementIssuedNotification::class, function (EnforcementIssuedNotification $notification, array $channels) use ($learner, $action): bool {
            $payload = $notification->toArray($learner);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && (int) $payload['enforcement_action_id'] === (int) $action->id
                && $payload['action_type'] === EnforcementActionType::TemporarySuspension->value
                && $payload['severity_level'] === ViolationSeverity::Major->value;
        });
    }

    public function test_suspension_issued_sends_in_app_and_email_notification(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');

        $action = EnforcementAction::query()->create([
            'user_id' => $learner->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(3),
            'status' => 'executed',
            'issued_by_admin_id' => $admin->id,
            'skip_ladder' => false,
        ]);

        $suspension = app(SuspensionService::class)->createFromEnforcementAction($action, $admin);

        Notification::assertSentTo($learner, SuspensionIssuedNotification::class, function (SuspensionIssuedNotification $notification, array $channels) use ($learner, $suspension): bool {
            $payload = $notification->toArray($learner);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && (int) $payload['user_suspension_id'] === (int) $suspension->id
                && $payload['suspension_status'] === 'active';
        });
    }

    public function test_appeal_submitted_notifies_admins(): void
    {
        Notification::fake();

        $adminOne = $this->createUserWithRole('admin');
        $adminTwo = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);

        $appeal = app(SuspensionAppealService::class)->submitAppeal(
            suspension: $suspension,
            user: $learner,
            reason: 'I am requesting reconsideration and can provide additional details.',
        );

        Notification::assertSentTo($adminOne, AppealSubmittedNotification::class, function (AppealSubmittedNotification $notification, array $channels) use ($adminOne, $appeal): bool {
            $payload = $notification->toArray($adminOne);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && (int) $payload['appeal_id'] === (int) $appeal->id
                && $payload['appeal_status'] === 'pending_review';
        });

        Notification::assertSentTo($adminTwo, AppealSubmittedNotification::class);
    }

    public function test_appeal_decision_notifies_user(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        $learner = $this->createUserWithRole('learner');
        $suspension = $this->createAppealableSuspension($learner);

        $appeal = SuspensionAppeal::query()->create([
            'user_suspension_id' => $suspension->id,
            'user_id' => $learner->id,
            'status' => 'pending_review',
            'appeal_reason' => 'Please review this decision.',
            'submitted_at' => now()->subHours(2),
        ]);

        app(SuspensionAppealService::class)->reviewAppeal(
            appeal: $appeal,
            admin: $admin,
            action: 'reject',
            decisionNotes: 'Decision upheld after review of the full evidence set.',
        );

        Notification::assertSentTo($learner, AppealDecisionNotification::class, function (AppealDecisionNotification $notification, array $channels) use ($learner, $appeal): bool {
            $payload = $notification->toArray($learner);

            return in_array('mail', $channels, true)
                && in_array('database', $channels, true)
                && (int) $payload['appeal_id'] === (int) $appeal->id
                && $payload['appeal_status'] === 'rejected';
        });
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
