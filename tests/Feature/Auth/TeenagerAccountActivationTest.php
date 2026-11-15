<?php

namespace Tests\Feature\Auth;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class TeenagerAccountActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_relationship_cannot_issue_activation(): void
    {
        [$guardian, $child, $relationship] = $this->eligibleActors();
        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'relationship_verified_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(MinorAccountHandoffService::class)->issueActivation($relationship);
    }

    public function test_reissue_expires_old_unused_code_and_existing_credentialed_teenager_is_not_eligible(): void
    {
        [$guardian, $child, $relationship] = $this->eligibleActors();
        $service = app(MinorAccountHandoffService::class);

        [$oldHandoff, $oldCode] = $service->issueActivation($relationship);
        [$newHandoff, $newCode] = $service->issueActivation($relationship);

        $this->assertNotSame($oldCode, $newCode);
        $this->assertNotNull($oldHandoff->fresh()->expires_at);
        $this->assertNull($service->findUsable($oldCode));
        $this->assertNotNull($service->findUsable(strtolower($newCode)));
        $this->assertStringNotContainsString($newCode, (string) $newHandoff->fresh()->token_hash);

        $child->forceFill(['minor_account_activation_required' => false])->save();

        $this->expectException(InvalidArgumentException::class);

        $service->issueActivation($relationship->fresh());
    }

    public function test_activation_sets_credentials_privacy_and_identity_case_without_verifying_email(): void
    {
        [$guardian, $child, $relationship] = $this->eligibleActors();
        [$handoff, $code] = app(MinorAccountHandoffService::class)->issueActivation($relationship);

        $response = $this->post(route('minor.activation.store'), [
            'code' => strtolower($code),
            'username' => 'new-teenager',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'privacy_notice' => '1',
        ]);

        $response->assertRedirect(route('learner.identity.create'));
        $this->assertAuthenticatedAs($child);

        $activated = $child->fresh(['learnerProfile', 'identityVerifications']);
        $this->assertFalse($activated->minor_account_activation_required);
        $this->assertNotNull($activated->minor_account_activated_at);
        $this->assertSame(config('learner_age_policy.guardian_consent_version'), $activated->minor_privacy_notice_version);
        $this->assertNotNull($activated->minor_privacy_accepted_at);
        $this->assertNull($activated->email_verified_at);
        $this->assertSame('new-teenager', $activated->learnerProfile?->username);
        $this->assertTrue(Hash::check('StrongPass1!', $activated->password));
        $this->assertCount(1, $activated->identityVerifications);
        $this->assertNull($activated->identityVerifications->first()->status);
        $this->assertNotNull($handoff->fresh()->used_at);
        $this->assertNull(app(MinorAccountHandoffService::class)->findUsable($code));
    }

    public function test_changed_dob_or_revoked_relationship_invalidates_activation_code(): void
    {
        [$guardian, $child, $relationship] = $this->eligibleActors();
        [$handoff, $code] = app(MinorAccountHandoffService::class)->issueActivation($relationship);

        $child->forceFill(['birthdate' => now()->subYears(18)->toDateString()])->save();

        $this->expectException(InvalidArgumentException::class);
        app(MinorAccountHandoffService::class)->consumeActivation($code, 'changed-dob', 'StrongPass1!');

        $this->assertNull($handoff->fresh()->used_at);
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount} */
    private function eligibleActors(): array
    {
        $guardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
            'parent_identity_sandbox_manual_review' => false,
            'email_verified_at' => now(),
        ]);

        $child = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'email' => null,
            'birthdate' => now()->subYears(15)->toDateString(),
            'minor_account_activation_required' => true,
        ]);
        $child->assignRole('learner');
        $child->learnerProfile()->create(['username' => 'proposed-teenager']);

        $relationship = ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'biological_mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'minor_registration_authorized_at' => now(),
            'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
            'minor_registration_authorized_by_user_id' => $guardian->id,
        ]);

        \Illuminate\Support\Facades\DB::table('minor_authorization_records')->insert([
            'parent_child_account_id' => $relationship->id,
            'guardian_user_id' => $guardian->id,
            'consent_version' => config('learner_age_policy.guardian_consent_version'),
            'privacy_notice_hash' => str_repeat('a', 64),
            'terms_hash' => str_repeat('b', 64),
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$guardian, $child, $relationship];
    }
}
