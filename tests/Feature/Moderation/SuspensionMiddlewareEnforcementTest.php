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
