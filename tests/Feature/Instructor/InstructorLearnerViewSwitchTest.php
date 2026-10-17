<?php

namespace Tests\Feature\Instructor;

use App\Models\RoleTransition;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
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

    public function test_promoted_learner_can_open_instructor_view_and_return_without_changing_roles_or_profile(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $user->assignRole('learner');
        $profile = $user->learnerProfile()->create([
            'username' => 'promoted-user-'.$user->id,
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);
        RoleTransition::query()->create([
            'user_id' => $user->id,
            'from_role' => 'learner',
            'to_role' => 'instructor',
            'transitioned_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, 'instructor');
        $rolesBefore = $user->roles()->pluck('name')->sort()->values()->all();

        $this->actingAs($user);
        $this->view('layouts.learner-header')
            ->assertSee('Switch to Instructor View')
            ->assertSee(route('instructor.dashboard'), false);
        $this->get(route('instructor.dashboard'))->assertOk();
        $this->get(route('instructor.switch-to-learner'))
            ->assertRedirect(route('learner.dashboard'));

        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame('instructor', $user->role);
        $this->assertSame($rolesBefore, $user->roles()->pluck('name')->sort()->values()->all());
        $this->assertSame($profile->id, $user->learnerProfile->id);
    }

    public function test_learner_without_instructor_access_cannot_switch_to_instructor_view(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $user->assignRole('learner');
        $this->actingAs($user);

        $this->view('layouts.learner-header')->assertDontSee('Switch to Instructor View');
        $this->get(route('instructor.dashboard'))->assertForbidden();
    }

    public function test_instructor_switch_accepts_the_module_creation_permission_used_by_instructor_routes(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $user->givePermissionTo('create modules');
        $this->actingAs($user);

        $this->view('layouts.learner-header')->assertSee('Switch to Instructor View');
        $this->get(route('instructor.dashboard'))->assertOk();
    }
}
