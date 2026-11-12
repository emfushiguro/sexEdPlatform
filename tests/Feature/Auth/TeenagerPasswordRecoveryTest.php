<?php

namespace Tests\Feature\Auth;

use App\Models\MinorAccountHandoff;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\TeenagerPasswordRecoveryRequestedNotification;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class TeenagerPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_reset_requests_keep_the_broker_path_and_username_requests_are_case_normalized(): void
    {
        Notification::fake();
        $adult = User::factory()->create();
        [$guardian, $teenager] = $this->eligibleActors();

        $emailResponse = $this->from(route('password.request'))
            ->post(route('password.email'), ['login' => $adult->email]);
        $usernameResponse = $this->from(route('password.request'))
            ->post(route('password.email'), ['login' => strtoupper($teenager->learnerProfile->username)]);

        $status = 'If an account exists for that email address, a password reset link has been sent.';
        $emailResponse->assertRedirect(route('password.request'))->assertSessionHas('status', $status);
        $usernameResponse->assertRedirect(route('password.request'))->assertSessionHas('status', $status);
        Notification::assertSentTo($adult, \Illuminate\Auth\Notifications\ResetPassword::class);
        $handoff = MinorAccountHandoff::query()->where('child_user_id', $teenager->id)->firstOrFail();
        $this->assertSame(MinorAccountHandoff::PURPOSE_PASSWORD_RESET, $handoff->purpose);
        $this->assertNull($handoff->token_hash);
        $this->assertNull($handoff->approved_at);
    }

    public function test_invalid_public_inputs_have_the_same_generic_response_and_do_not_create_recovery_rows(): void
    {
        $this->post(route('password.email'), ['login' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors();

        $this->post(route('password.email'), ['login' => 'unknown-learner'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors();

        $adult = User::factory()->create([
            'role' => 'learner',
            'email' => null,
            'birthdate' => now()->subYears(30)->toDateString(),
        ]);
        $adult->assignRole('learner');
        $adult->learnerProfile()->create(['username' => 'adult-without-email']);
        $this->post(route('password.email'), ['login' => 'adult-without-email'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors();

        $teenager = User::factory()->create([
            'role' => 'learner',
            'email' => null,
            'birthdate' => now()->subYears(15)->toDateString(),
            'minor_account_activation_required' => false,
            'minor_account_activated_at' => now(),
        ]);
        $teenager->assignRole('learner');
        $teenager->learnerProfile()->create(['username' => 'no-guardian']);

        $responses = [
            $this->post(route('password.email'), ['login' => 'unknown@example.com']),
            $this->post(route('password.email'), ['login' => 'no-guardian']),
        ];

        foreach ($responses as $response) {
            $response->assertRedirect(route('password.request'))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('minor_account_handoffs', 0);
        $this->assertNotNull($adult->fresh());
    }

    public function test_guardian_approval_reveals_one_code_and_successful_reset_is_single_use(): void
    {
        [$guardian, $teenager, $relationship] = $this->eligibleActors();
        $service = app(MinorAccountHandoffService::class);
        $service->requestPasswordReset($teenager);
        $handoff = MinorAccountHandoff::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($guardian)
            ->post(route('parent.children.minor-password-reset.approve', $handoff));

        $response->assertOk();
        $code = (string) $response->viewData('code');
        $this->assertSame(16, strlen($code));
        $approved = $handoff->fresh();
        $this->assertNotNull($approved->approved_at);
        $this->assertNotSame($code, $approved->token_hash);
        $this->assertSame(app(MinorAccountHandoffService::class)->digestForStorage($code), $approved->token_hash);

        $this->actingAs($guardian)
            ->post(route('parent.children.minor-password-reset.approve', $approved))
            ->assertSessionHasErrors('recovery');

        $oldRememberToken = $teenager->remember_token;
        $this->post(route('minor.password.reset.store'), [
            'code' => strtolower($code),
            'username' => strtoupper($teenager->learnerProfile->username),
            'password' => 'NewStrongPass1!',
            'password_confirmation' => 'NewStrongPass1!',
        ])->assertRedirect(route('login'));

        $updated = $teenager->fresh();
        $this->assertTrue(Hash::check('NewStrongPass1!', $updated->password));
        $this->assertNotSame($oldRememberToken, $updated->remember_token);
        $this->assertNotNull($approved->fresh()->used_at);
        $this->assertSame($relationship->id, $approved->fresh()->parent_child_account_id);

        $this->post(route('minor.password.reset.store'), [
            'code' => $code,
            'username' => $teenager->learnerProfile->username,
            'password' => 'AnotherStrong1!',
            'password_confirmation' => 'AnotherStrong1!',
        ])->assertSessionHasErrors('code');
    }

    public function test_different_guardian_and_revoked_or_stale_relationship_cannot_approve(): void
    {
        [$guardian, $teenager, $relationship] = $this->eligibleActors();
        $otherGuardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        $service = app(MinorAccountHandoffService::class);
        $service->requestPasswordReset($teenager);
        $handoff = MinorAccountHandoff::query()->latest('id')->firstOrFail();

        $this->actingAs($otherGuardian)
            ->post(route('parent.children.minor-password-reset.approve', $handoff))
            ->assertSessionHasErrors('recovery');

        $relationship->update(['minor_registration_consent_version' => 'old-version']);
        $this->actingAs($guardian)
            ->post(route('parent.children.minor-password-reset.approve', $handoff))
            ->assertSessionHasErrors('recovery');
    }

    public function test_adult_reclassification_does_not_fall_back_to_the_former_guardian(): void
    {
        [$guardian, $teenager] = $this->eligibleActors();
        $service = app(MinorAccountHandoffService::class);
        $service->requestPasswordReset($teenager);
        $this->assertDatabaseCount('minor_account_handoffs', 1);

        $teenager->forceFill(['birthdate' => now()->subYears(18)->toDateString()])->save();

        $this->post(route('password.email'), ['login' => $teenager->learnerProfile->username])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('minor_account_handoffs', 1);
        $this->get(route('minor.password.reset', ['code' => 'invalid']))
            ->assertOk()
            ->assertSee('support');
        $this->assertNotNull($guardian->fresh());
    }

    public function test_notification_contains_no_code_password_or_private_evidence(): void
    {
        [$guardian, $teenager] = $this->eligibleActors();
        $handoff = new MinorAccountHandoff([
            'child_user_id' => $teenager->id,
            'guardian_user_id' => $guardian->id,
            'purpose' => MinorAccountHandoff::PURPOSE_PASSWORD_RESET,
            'token_hash' => null,
            'expires_at' => now()->addMinutes(30),
        ]);

        $notification = new TeenagerPasswordRecoveryRequestedNotification($teenager, $handoff);
        $data = $notification->toArray($guardian);
        $mail = $notification->toMail($guardian);

        $serialized = json_encode([$data, $mail->viewData]);
        $this->assertStringNotContainsString('password', strtolower((string) $serialized));
        $this->assertStringNotContainsString('identity', strtolower((string) $serialized));
        $this->assertStringNotContainsString('document', strtolower((string) $serialized));
    }

    public function test_service_rejects_expired_or_suspended_recovery(): void
    {
        [$guardian, $teenager] = $this->eligibleActors();
        $service = app(MinorAccountHandoffService::class);
        $service->requestPasswordReset($teenager);
        $handoff = MinorAccountHandoff::query()->latest('id')->firstOrFail();
        $handoff->update(['expires_at' => now()->subMinute()]);

        $this->expectException(InvalidArgumentException::class);
        $service->approvePasswordReset($handoff, $guardian);
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount} */
    private function eligibleActors(): array
    {
        $guardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
            'guardian_onboarding_status' => 'completed',
            'email_verified_at' => now(),
        ]);

        $teenager = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'email' => null,
            'birthdate' => now()->subYears(15)->toDateString(),
            'minor_account_activation_required' => false,
            'minor_account_activated_at' => now(),
            'password' => Hash::make('OldStrongPass1!'),
        ]);
        $teenager->assignRole('learner');
        $teenager->learnerProfile()->create(['username' => 'teen-'.Str::lower(Str::random(8))]);

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

        return [$guardian, $teenager, $relationship];
    }
}
