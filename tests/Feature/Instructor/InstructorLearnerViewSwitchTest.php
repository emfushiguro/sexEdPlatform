<?php

namespace Tests\Feature\Instructor;

use App\Models\RoleTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorLearnerViewSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_transitioned_instructor_can_switch_to_learner_dashboard_without_auth_or_role_changes(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'instructor']);
        $user->assignRole('instructor');

        $user->learnerProfile()->create([
            'username' => 'switch-user-' . $user->id,
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        RoleTransition::query()->create([
            'user_id' => $user->id,
            'from_role' => 'learner',
            'to_role' => 'instructor',
            'transitioned_at' => now(),
        ]);

        $rolesBefore = $user->roles()->pluck('name')->sort()->values()->all();

        $this->actingAs($user)
            ->get(route('instructor.switch-to-learner'))
            ->assertRedirect(route('learner.dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $rolesAfter = $user->roles()->pluck('name')->sort()->values()->all();

        $this->assertSame($rolesBefore, $rolesAfter);
        $this->assertTrue($user->canSwitchToLearnerView());
    }

    public function test_standalone_instructor_cannot_switch_to_learner_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['role' => 'instructor']);
        $user->assignRole('instructor');

        $this->actingAs($user)
            ->get(route('instructor.switch-to-learner'))
            ->assertForbidden();
    }
}
