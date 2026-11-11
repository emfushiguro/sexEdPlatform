<?php

namespace Tests\Feature\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionMiddlewareEnforcementTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_suspended_user_is_redirected_from_authenticated_routes(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->makeActiveSuspension($user);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('moderation.suspension-status'));
    }

    public function test_allowlist_routes_remain_accessible_for_suspended_users(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->makeActiveSuspension($user);

        $response = $this->actingAs($user)->get(route('moderation.suspension-status'));

        $response->assertOk();
    }

    public function test_non_suspended_users_continue_normally(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_expired_suspension_restores_access_and_account_status_on_next_request(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED]);
        $suspension = $this->makeActiveSuspension($user);
        $suspension->forceFill(['ends_at' => now()->subMinute()])->save();
        $suspension->enforcementAction->forceFill(['ends_at' => now()->subMinute()])->save();

        $this->actingAs($user)->get('/profile')->assertOk();

        $this->assertSame('expired', $suspension->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
    }

    public function test_expiring_one_suspension_does_not_restore_access_if_another_is_active(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED]);
        $expired = $this->makeActiveSuspension($user);
        $active = $this->makeActiveSuspension($user);
        $expired->forceFill(['ends_at' => now()->subMinute()])->save();
        $expired->enforcementAction->forceFill(['ends_at' => now()->subMinute()])->save();

        $this->actingAs($user)->get('/profile')
            ->assertRedirect(route('moderation.suspension-status'));

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('active', $active->fresh()->status);
        $this->assertSame(User::STATUS_SUSPENDED, $user->fresh()->status);
    }

    private function makeActiveSuspension(User $user): UserSuspension
    {
        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::TemporarySuspension,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        return UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'appeal_status' => 'none',
        ]);
    }
}
