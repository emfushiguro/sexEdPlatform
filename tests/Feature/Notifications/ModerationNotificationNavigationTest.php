<?php

namespace Tests\Feature\Notifications;

use App\Models\EnforcementAction;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use App\Support\NotificationDeepLinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;

class ModerationNotificationNavigationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    #[DataProvider('notificationRoles')]
    public function test_existing_notifications_render_public_decision_for_each_role(string $role, bool $guardian, string $route): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\EnsureLearnerIdentityVerified::class,
            \App\Http\Middleware\EnsureProfileCompleted::class, \App\Http\Middleware\EnsureLearnerContactReady::class]);
        $user = User::factory()->create(['role' => $role, 'birthdate' => '1990-01-01', 'is_parent_registration' => $guardian,
            'parent_verification_status' => $guardian ? 'approved' : null, 'guardian_onboarding_status' => $guardian ? 'completed' : null]);
        $user->assignRole($role);
        $suspension = UserSuspension::create(['user_id' => $user->id, 'status' => 'revoked', 'starts_at' => now()->subHour(), 'ends_at' => now(), 'appeal_status' => 'resolved']);
        $user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\Moderation\AppealDecisionNotification::class,
            'data' => ['type' => 'moderation_appeal_decision', 'suspension_id' => $suspension->id, 'user_id' => $user->id, 'appeal_status' => 'rejected']]);
        $this->actingAs($user)->get(route($route))->assertOk()->assertSeeText('Your suspension appeal was rejected.');
    }

    public static function notificationRoles(): array
    {
        return [['admin', false, 'admin.notifications.index'], ['instructor', false, 'instructor.notifications.index'],
            ['learner', false, 'learner.notifications.index'], ['learner', true, 'learner.notifications.index']];
    }

    public function test_moderation_links_enforce_recipient_and_record_ownership(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $other = User::factory()->create(['role' => 'learner']);
        $other->assignRole('learner');
        $action = EnforcementAction::create(['user_id' => $owner->id, 'action_type' => 'temporary_suspension',
            'severity_level' => 'major', 'trigger_type' => 'manual', 'starts_at' => now(), 'ends_at' => now()->addHours(8), 'status' => 'executed']);
        $suspension = UserSuspension::create(['user_id' => $owner->id, 'enforcement_action_id' => $action->id,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addHours(8), 'appeal_status' => 'none']);
        $appeal = SuspensionAppeal::create(['user_id' => $owner->id, 'user_suspension_id' => $suspension->id,
            'status' => 'pending_review', 'appeal_reason' => 'Review the full context.', 'submitted_at' => now()]);
        $resolver = app(NotificationDeepLinkResolver::class);
        $payload = ['type' => 'moderation_appeal_submitted', 'appeal_id' => $appeal->id, 'user_id' => $owner->id];
        $this->assertSame(route('admin.moderation-appeals.show', $appeal, false), $resolver->resolve($payload, '/fallback', $admin));
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $other));
        $payload = ['type' => 'moderation_suspension_issued', 'user_suspension_id' => $suspension->id,
            'user_id' => $owner->id, 'action_url' => 'https://evil.example/'];
        $this->assertSame(route('moderation.suspension-status', [], false), $resolver->resolve($payload, '/fallback', $owner));
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $other));
        $payload['user_id'] = $other->id;
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $other));
        $payload['user_suspension_id'] = -1;
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback', $owner));
        $this->assertSame('/fallback', $resolver->resolve(['type' => 'moderation_appeal_decision'], '/fallback', $owner));
        $this->assertSame('/fallback', $resolver->resolve(['type' => 'unknown', 'action_url' => '//evil.example/'], '/fallback', $owner));
        $this->assertSame('/safe-existing-destination', $resolver->resolve(['type' => 'moderation_future_notice', 'action_url' => '/safe-existing-destination'], '/fallback', $owner));

        $suspension->update(['status' => 'revoked']);
        $payload = ['type' => 'moderation_appeal_decision', 'suspension_id' => $suspension->id, 'appeal_id' => $appeal->id, 'user_id' => $owner->id, 'appeal_status' => 'approved'];
        $history = route('moderation.appeals.create', $suspension, false);
        $this->assertSame($history, $resolver->resolve($payload, '/fallback', $owner));
        $this->actingAs($owner)->get($history)->assertOk()->assertSeeText('Appeal History');
        UserSuspension::create(['user_id' => $owner->id, 'enforcement_action_id' => $action->id, 'status' => 'active',
            'starts_at' => now(), 'ends_at' => now()->addHours(8), 'appeal_status' => 'none']);
        $this->assertSame($history, $resolver->resolve($payload, '/fallback', $owner));
        $this->get($history)->assertOk()->assertSeeText('Appeal History');
    }
}
