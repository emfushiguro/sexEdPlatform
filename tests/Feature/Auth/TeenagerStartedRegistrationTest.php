<?php

namespace Tests\Feature\Auth;

use App\Models\MinorRegistrationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Auth\MinorAccountHandoffService;
use App\Support\AccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeenagerStartedRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_request_stores_only_encrypted_minimum_data_and_does_not_create_an_account(): void
    {
        $userCount = User::query()->count();

        $response = $this->post(route('minor.registration.store'), [
            'first_name' => 'Private',
            'last_name' => 'Teenager',
            'birthdate' => now()->subYears(15)->toDateString(),
            'guardian_email' => 'Guardian@Example.test',
        ]);

        $response->assertRedirect(route('minor.registration.create'));
        $this->assertSame($userCount, User::query()->count());
        $this->assertDatabaseCount('parent_child_accounts', 0);
        $this->assertDatabaseCount('learner_identity_verifications', 0);

        $request = MinorRegistrationRequest::query()->firstOrFail();
        $raw = DB::table('minor_registration_requests')->whereKey($request->id)->first();
        $this->assertNotSame('guardian@example.test', $raw->guardian_email);
        $this->assertStringNotContainsString('Private', (string) $raw->minor_payload);
        $this->assertSame('guardian@example.test', $request->fresh()->guardian_email);
        $this->assertSame('Private', $request->fresh()->minor_payload['first_name']);
    }

    public function test_non_teenager_request_is_rejected_and_public_response_does_not_depend_on_account_existence(): void
    {
        $underage = $this->post(route('minor.registration.store'), [
            'first_name' => 'Young',
            'last_name' => 'Learner',
            'birthdate' => now()->subYears(9)->toDateString(),
            'guardian_email' => 'guardian-one@example.test',
        ]);

        $underage->assertSessionHasErrors('birthdate');
        $this->assertDatabaseCount('minor_registration_requests', 0);

        $known = $this->post(route('minor.registration.store'), [
            'first_name' => 'Eligible',
            'last_name' => 'Teenager',
            'birthdate' => now()->subYears(15)->toDateString(),
            'guardian_email' => 'known@example.test',
        ]);
        $unknown = $this->post(route('minor.registration.store'), [
            'first_name' => 'Eligible',
            'last_name' => 'Teenager',
            'birthdate' => now()->subYears(15)->toDateString(),
            'guardian_email' => 'unknown@example.test',
        ]);

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
        $this->assertSame(
            $known->getSession()->get('success'),
            $unknown->getSession()->get('success'),
        );
    }

    public function test_matching_guardian_can_accept_a_guest_request_without_completing_it(): void
    {
        $guardian = $this->guardian('guardian@example.test');
        $token = app(MinorAccountHandoffService::class)->generateCode();
        $registrationRequest = MinorRegistrationRequest::query()->create([
            'guardian_email' => $guardian->email,
            'guardian_email_fingerprint' => AccountEmail::fingerprint($guardian->email),
            'minor_payload' => [
                'first_name' => 'Requested',
                'last_name' => 'Teenager',
                'birthdate' => now()->subYears(15)->toDateString(),
            ],
            'token_hash' => app(MinorAccountHandoffService::class)->digestForStorage($token),
            'status' => MinorRegistrationRequest::STATUS_PENDING,
            'expires_at' => now()->addDays(14),
        ]);

        $response = $this->actingAs($guardian)->get(route('minor.registration.accept', $token));

        $response->assertRedirect(route('parent.create-child'));
        $this->assertSame($registrationRequest->id, session('minor_registration_request_id'));
        $this->assertSame('Requested', session('pending_child_registration.first_name'));
        $this->assertSame(MinorRegistrationRequest::STATUS_PENDING, $registrationRequest->fresh()->status);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_forwarded_request_is_not_available_to_a_different_guardian_email(): void
    {
        $guardian = $this->guardian('guardian@example.test');
        $otherGuardian = $this->guardian('other@example.test');
        $token = $this->createRequestFor($guardian, app(MinorAccountHandoffService::class)->generateCode());

        $response = $this->actingAs($otherGuardian)->get(route('minor.registration.accept', $token['code']));

        $response->assertRedirect(route('minor.registration.create'));
        $response->assertSessionHasErrors('guardian_email');
        $this->assertNull(session('pending_child_registration'));
        $this->assertSame(MinorRegistrationRequest::STATUS_PENDING, $token['request']->fresh()->status);
    }

    public function test_existing_teenager_request_preserves_credentials_and_links_the_existing_user(): void
    {
        $teenager = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(15)->toDateString(),
            'email' => 'teenager@example.test',
        ]);
        $teenager->assignRole('learner');
        $teenager->learnerProfile()->create(['username' => 'existing-teenager']);
        $passwordHash = $teenager->password;

        $response = $this->actingAs($teenager)->post(route('minor.registration.store'), [
            'guardian_email' => 'guardian@example.test',
        ]);

        $response->assertRedirect(route('minor.registration.create'));
        $request = MinorRegistrationRequest::query()->firstOrFail();
        $this->assertSame($teenager->id, $request->existing_child_user_id);
        $this->assertNull($request->minor_payload);
        $this->assertSame($passwordHash, $teenager->fresh()->password);
        $this->assertSame('existing-teenager', $teenager->fresh()->learnerProfile?->username);
    }

    public function test_owner_can_record_current_authorization_without_approving_relationship_or_identity(): void
    {
        $guardian = $this->guardian('guardian@example.test');
        $child = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(15)->toDateString(),
        ]);
        $child->assignRole('learner');
        $child->learnerProfile()->create(['username' => 'legacy-teenager']);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'biological_mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
        ]);

        $this->actingAs($guardian)
            ->post(route('parent.children.minor-authorization', $relationship))
            ->assertRedirect();

        $authorized = $relationship->fresh();
        $this->assertSame(ParentChildAccount::STATUS_ACTIVE, $authorized->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_VERIFIED, $authorized->relationship_verified_status);
        $this->assertSame($guardian->id, $authorized->minor_registration_authorized_by_user_id);
        $this->assertSame(config('learner_age_policy.guardian_consent_version'), $authorized->minor_registration_consent_version);
        $this->assertDatabaseCount('learner_identity_verifications', 0);
    }

    /** @return array{request: MinorRegistrationRequest, code: string} */
    private function createRequestFor(User $guardian, string $code): array
    {
        $request = MinorRegistrationRequest::query()->create([
            'guardian_email' => $guardian->email,
            'guardian_email_fingerprint' => AccountEmail::fingerprint($guardian->email),
            'minor_payload' => [
                'first_name' => 'Requested',
                'last_name' => 'Teenager',
                'birthdate' => now()->subYears(15)->toDateString(),
            ],
            'token_hash' => app(MinorAccountHandoffService::class)->digestForStorage($code),
            'status' => MinorRegistrationRequest::STATUS_PENDING,
            'expires_at' => now()->addDays(14),
        ]);

        return ['request' => $request, 'code' => $code];
    }

    private function guardian(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'guardian_onboarding_status' => 'completed',
            'birthdate' => now()->subYears(40)->toDateString(),
            'email_verified_at' => now(),
        ]);
    }
}
