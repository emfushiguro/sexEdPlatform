<?php

namespace Tests\Feature\Auth;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeenagerAccessGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_activated_no_email_teenager_can_reach_identity_but_waits_for_identity_approval_before_learning(): void
    {
        [$teenager, $case] = $this->teenager();
        $this->actingAs($teenager);

        $this->get(route('learner.identity.create'))
            ->assertOk()
            ->assertSee('Identity Verification');
        $this->get(route('learner.dashboard'))
            ->assertRedirect(route('profile.complete'));

        $case->update(['status' => 'approved']);
        $this->get(route('learner.dashboard'))
            ->assertRedirect(route('profile.complete'));
    }

    public function test_unactivated_or_stale_guardian_state_fails_closed_with_html_and_json_responses(): void
    {
        [$teenager, $case, $relationship] = $this->teenager(activated: false);
        $this->actingAs($teenager);

        $this->get(route('learner.identity.create'))
            ->assertRedirect(route('learner.eligibility.status'));
        $this->getJson(route('learner.dashboard'))
            ->assertForbidden()
            ->assertJson(['code' => 'completion_required']);

        $teenager->forceFill([
            'minor_account_activation_required' => false,
            'minor_account_activated_at' => now(),
        ])->save();
        $relationship->update(['minor_registration_consent_version' => 'old-version']);

        $this->get(route('learner.identity.create'))
            ->assertRedirect(route('learner.eligibility.status'));
        $this->get(route('learner.eligibility.status'))
            ->assertOk()
            ->assertSee('Guardian');
        $this->assertSame($case->id, $case->fresh()->id);
    }

    public function test_adult_still_requires_own_verified_email(): void
    {
        $adult = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(30)->toDateString(),
            'email_verified_at' => null,
        ]);
        $adult->assignRole('learner');
        app(LearnerIdentityRequirement::class)->createForNewLearner($adult);

        $this->actingAs($adult)
            ->get(route('learner.identity.create'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_ineligible_dob_is_redirected_to_plain_status_page(): void
    {
        $underage = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(9)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $underage->assignRole('learner');

        $this->actingAs($underage)
            ->get(route('learner.dashboard'))
            ->assertRedirect(route('learner.eligibility.status'));
        $this->get(route('learner.eligibility.status'))
            ->assertOk()
            ->assertSee('A Guardian must help')
            ->assertDontSee($underage->birthdate->toDateString());
    }

    public function test_restricted_unactivated_teenager_cannot_log_in_with_the_proposed_credentials(): void
    {
        $teenager = User::factory()->create([
            'role' => 'learner',
            'email' => null,
            'birthdate' => now()->subYears(15)->toDateString(),
            'minor_account_activation_required' => true,
            'minor_account_activated_at' => null,
            'password' => Hash::make('StrongPass1!'),
        ]);
        $teenager->assignRole('learner');
        $teenager->learnerProfile()->create(['username' => 'restricted-teenager']);

        $this->post(route('login'), [
            'email' => 'restricted-teenager',
            'password' => 'StrongPass1!',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @return array{0: User, 1: \App\Models\LearnerIdentityVerification, 2: ParentChildAccount} */
    private function teenager(bool $activated = true): array
    {
        $guardian = User::factory()->create([
            'email' => 'guardian-'.fake()->unique()->safeEmail(),
            'birthdate' => now()->subYears(40)->toDateString(),
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'guardian_onboarding_status' => 'completed',
            'email_verified_at' => now(),
        ]);

        $teenager = User::factory()->create([
            'role' => 'learner',
            'email' => null,
            'birthdate' => now()->subYears(15)->toDateString(),
            'status' => User::STATUS_ACTIVE,
            'minor_account_activation_required' => ! $activated,
            'minor_account_activated_at' => $activated ? now() : null,
        ]);
        $teenager->assignRole('learner');
        $teenager->learnerProfile()->create(['username' => 'teen-'.fake()->unique()->userName()]);

        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $teenager->id,
            'relationship_type' => 'biological_mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'minor_registration_authorized_at' => now(),
            'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
            'minor_registration_authorized_by_user_id' => $guardian->id,
        ]);
        $case = app(LearnerIdentityRequirement::class)->createForNewLearner($teenager);

        return [$teenager, $case, $relationship];
    }
}
